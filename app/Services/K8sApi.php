<?php

namespace App\Services;

use App\Models\Ns;
use App\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Transformers\NsNameTransformer;

class K8sApi
{
    const tokenFile = '/var/run/secrets/kubernetes.io/serviceaccount/token';
    const rootCAFile = '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt';

    protected string $domain;
    protected string $token;
    protected bool $inK8s = false;

    public function __construct()
    {
        if (config('k8s.cluster_url') && config('k8s.bearer_token')) {
            $this->domain = config('k8s.cluster_url', '');
            $this->token = config('k8s.bearer_token', '');
        } else {
            $host = getenv('KUBERNETES_SERVICE_HOST');
            $port = getenv('KUBERNETES_SERVICE_PORT');
            if ($host && $port) {
                $this->inK8s = true;
                $this->domain = "https://{$host}:{$port}";
                $this->token = file_get_contents(self::tokenFile);
            }
        }
    }

    public function topNsPods($ns, $key = null)
    {
        $ns = NsNameTransformer::transform($ns);
        $res = $this->http()->get("/apis/metrics.k8s.io/v1beta1/namespaces/{$ns}/pods");

        return $res->json($key);
    }

    public function getPod($ns, $pod, $key = null)
    {
        $ns = NsNameTransformer::transform($ns);

        $res = $this->http()->get("/api/v1/namespaces/{$ns}/pods/{$pod}");

        return $res->json($key);
    }

    public function getPods($ns, $labels = null, $key = null)
    {
        $ns = NsNameTransformer::transform($ns);

        $res = $this->http()->get(
            "/api/v1/namespaces/{$ns}/pods",
            $labels
            ? ['labelSelector' => $labels]
            : []
        );

        return $res->json($key);
    }

    public function getPodStatus(string $ns, string $pod, $key = null)
    {
        $ns = NsNameTransformer::transform($ns);
        $res = $this
            ->http()
            ->get(
                "/api/v1/namespaces/{$ns}/pods/{$pod}/status",
                ['pretty' => true]
            );

        return $res->json($key);
    }

    public function isPodReady(string $ns, string $pod): bool
    {
        return ! collect($this->getPodStatus($ns, $pod, 'status.containerStatuses.*.ready'))->contains(false);
    }

    /**
     * GET /api/v1/namespaces/{namespace}/pods/{name}/log
     *
     * @param $ns
     * @param $pod
     * @return string
     *
     * @throws \Exception
     * @author duc <1025434218@qq.com>
     */
    public function getPodLog(string $ns, string $pod, string $container = null)
    {
        $ns = NsNameTransformer::transform($ns);
        $params = ['pretty' => true, 'tailLines' => 1000];
        if ($container) {
            $params['container'] = $container;
        }
        $res = $this
            ->http()
            ->get(
                "/api/v1/namespaces/{$ns}/pods/{$pod}/log",
                $params
            );

        return $res->body();
    }

    /**
     * /apis/apps/v1/namespaces/{namespace}/deployments
     *
     * @param $ns
     *
     * @param null $key
     * @return array|mixed
     * @throws \Exception
     * @author duc <1025434218@qq.com>
     */
    public function getDeployments($ns, $key = null)
    {
        $ns = NsNameTransformer::transform($ns);

        $res = $this->http()->get("/apis/apps/v1/namespaces/{$ns}/deployments");

        return $res->json($key);
    }

    /**
     * /apis/apps/v1/namespaces/{namespace}/deployments/{name}
     *
     * @param $ns
     * @param $name
     * @param null $key
     * @return array|mixed
     * @author duc <1025434218@qq.com>
     */
    public function showDeployments(string $ns, string $name, $key = null)
    {
        $ns = NsNameTransformer::transform($ns);

        $res = $this->http()->get("/apis/apps/v1/namespaces/{$ns}/deployments/{$name}");

        return $res->json($key);
    }

    public function getDeploymentsByLabelSelector($ns, string $labels = null, $key = null)
    {
        $ns = NsNameTransformer::transform($ns);

        $res = $this->http()->get(
            "/apis/apps/v1/namespaces/{$ns}/deployments",
            $labels
                ? ['labelSelector' => $labels]
                : []
        );

        return $res->json($key);
    }

    public function hasNamespace($ns)
    {
        $ns = NsNameTransformer::transform($ns);

        return in_array($ns, $this->getActiveNamespaces());
    }

    public function hasSecret($ns, $secretName)
    {
        $ns = NsNameTransformer::transform($ns);
        $res = $this->http()->get("/api/v1/namespaces/{$ns}/secrets/{$secretName}");

        if ($res->successful()) {
            return true;
        }

        return false;
    }

    public function getSecret($ns, $secretName, $key = null)
    {
        $ns = NsNameTransformer::transform($ns);
        $res = $this->http()->get("/api/v1/namespaces/{$ns}/secrets/{$secretName}");

        return $res->json($key);
    }

    public function deleteSecret($ns, $secretName)
    {
        $ns = NsNameTransformer::transform($ns);
        $res = $this->http()->delete("/api/v1/namespaces/{$ns}/secrets/{$secretName}");

        if ($res->successful() || $res->status() == 404) {
            return true;
        }

        throw new \Exception($res->json());
    }

    public function createSecret($ns, $secretName, $dockerAuth, $dockerServer = null, $key = null)
    {
        $ns = NsNameTransformer::transform($ns);
        $dockerServer = $dockerServer ?: 'https://index.docker.io/v1/';
        $data = <<<DATA
{
    "auths": {
        "$dockerServer": {
            "auth": "$dockerAuth"
        }
    }
}
DATA;
        $res = $this->http()->post("/api/v1/namespaces/{$ns}/secrets", [
            'metadata' => [
                'name' => $secretName,
            ],
            'type' => 'kubernetes.io/dockerconfigjson',
            'data' => [
                '.dockerconfigjson' => base64_encode($data),
            ],
        ]);
        if ($res->successful()) {
            return $res->json($key);
        }

        throw new \Exception($res->json());
    }

    public function createNamespaces($ns)
    {
        $ns = NsNameTransformer::transform($ns);

        if ($this->hasNamespace($ns)) {
            return true;
        }

        $res = $this->http()->post('/api/v1/namespaces', [
            'apiVersion' => 'v1',
            'kind'       => 'Namespace',
            'metadata'   => [
                'name' => $ns,
            ],
        ]);

        if ($res->successful() && $res->json('code') == 0) {
            return true;
        }

        throw new \Exception($res->body());
    }

    public function getActiveNamespaces()
    {
        $res = $this->http()->get('/api/v1/namespaces');

        return collect($res->json('items'))
            ->pluck('status.phase', 'metadata.name')
            ->filter(fn ($item) => $item == 'Active')
            ->keys()
            ->toArray();
    }

    /**
     * DELETE /api/v1/namespaces/{name}
     *
     * @param $ns
     * @return bool
     *
     * @author duc <1025434218@qq.com>
     */
    public function deleteNamespace(string $ns)
    {
        $ns = NsNameTransformer::transform($ns);

        if ($this->hasNamespace($ns)) {
            $res = $this->http()->delete("/api/v1/namespaces/${ns}");
            if ($res->json('status.phase') == 'Terminating') {
                return true;
            }

            logger()->error("deleteNamespace {$ns}", [$res->status(), $res->body()]);

            return false;
        }

        return true;
    }

    public function getIngress(Ns $ns, $key = null)
    {
        return $ns->projects->mapWithKeys(function (Project $project) use ($ns, $key) {
            $namespace = NsNameTransformer::transform($ns->name);

            return [
                $project->name => collect(
                    $this
                        ->http()
                        ->get(
                            "/apis/extensions/v1beta1/namespaces/{$namespace}/ingresses",
                            [
                                // 'labelSelector' => 'app.kubernetes.io/instance='.$project->name,
                            ]
                        )
                        ->json($key)
                ),
            ];
        });
    }

    public function getProjectIngress(Project $project, $key = null)
    {
        $namespace = NsNameTransformer::transform($project->namespace->name);

        return [
            $project->name => collect(
                $this
                    ->http()
                    ->get(
                        "/apis/extensions/v1beta1/namespaces/{$namespace}/ingresses",
                        [
                            'labelSelector' => 'app.kubernetes.io/instance=' . $project->name,
                        ]
                    )
                    ->json($key)
            ),
        ];
    }

    public function getServices(Ns $ns, $key = null)
    {
        return $ns->projects->mapWithKeys(function (Project $project) use ($ns, $key) {
            $namespace = NsNameTransformer::transform($ns->name);

            return [
                $project->name => collect(
                    $this->http()->get(
                        "/api/v1/namespaces/{$namespace}/services",
                        [
                            'labelSelector' => 'app.kubernetes.io/instance=' . $project->name,
                        ]
                    )
                        ->json($key)
                ),
            ];
        });
    }

    public function getProjectServices(Project $project, $key = null): array
    {
        $namespace = NsNameTransformer::transform($project->namespace->name);

        return [
            $project->name => collect(
                $this->http()->get(
                    "/api/v1/namespaces/{$namespace}/services",
                    [
                        'labelSelector' => 'app.kubernetes.io/instance=' . $project->name,
                    ]
                )
                    ->json($key)
            ),
        ];
    }

    public function nodePortUrls(Project $project): array
    {
        $res = $this->getProjectServices($project, 'items');

        return collect($res)
            ->map
            ->filter(function ($item) {
                return Arr::get($item, 'spec.type') == 'NodePort' && count(Arr::get($item, 'spec.ports')) >= 1;
            })
            ->map
            ->map(function ($item) {
                $host = Arr::get($item, 'status.loadBalancer.ingress.0.hostname') ?? config('k8s.cluster_ip');

                return collect(Arr::get($item, 'spec.ports', []))->map(function ($data) use ($host) {
                    $protocol = 'http://';

                    if (Str::contains(strtolower(Arr::get($data, 'name')), ['tcp', 'rpc', 'grpc'])) {
                        $protocol = 'tcp://';
                    }

                    return $protocol . $host . ':' . Arr::get($data, 'nodePort');
                });
            })
            ->flatten(1)
            ->values()
            ->toArray();
    }

    public function ingressUrls(Project $project): array
    {
        $res = $this->getProjectIngress($project, 'items.*.spec');

        $https = collect($res)
            ->map
            ->pluck('tls.*.hosts')
            ->mapWithKeys(function ($item, $project) {
                return [
                    $project => collect($item)
                        ->flatten()
                        ->filter()
                        ->values()
                        ->map(fn ($url) => 'https://' . $url), ];
            })->toArray();
        $http = collect($res)
            ->map
            ->pluck('rules.*.host')
            ->mapWithKeys(function ($item, $project) {
                return [
                    $project => collect($item)
                        ->flatten()
                        ->filter()
                        ->values()
                        ->map(fn ($url) => 'http://' . $url), ];
            })->toArray();

        return [$https, $http];
    }

    private function http()
    {
        $http = Http::withHeaders(['Authorization' => 'bearer ' . $this->token])
            ->baseUrl($this->domain);
        if ($this->inK8s) {
            $http->withOptions(['verify' => self::rootCAFile]);
        } else {
            $http->withOptions(['verify' => false]);
        }

        return $http;
    }
}
