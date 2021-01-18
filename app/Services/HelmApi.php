<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Configs\ChartValuesImp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Transformers\NsNameTransformer;

class HelmApi
{
    protected string $domain;
    protected K8sApi $k8sApi;

    public function __construct(K8sApi $api)
    {
        $this->k8sApi = $api;
        $this->domain = config('k8s.helm_api_base_url');
    }

    /**
     * /api/namespaces/:namespace/releases
     * @param $ns
     * @param null $key
     * @param null $default
     * @return array|mixed
     * @author duc <1025434218@qq.com>
     */
    public function list($ns, $key = null, $default = null)
    {
        $ns = NsNameTransformer::transform($ns);
        $res = $this->http()->get("/api/namespaces/{$ns}/releases");

        return $res->json($key, $default);
    }

    public function deploy(string $ns, string $project, ChartValuesImp $imp)
    {
        return $this->upgrade($ns, $project, $imp);
    }

    public function show($ns, $release, $key = null, $info = 'all')
    {
        $ns = NsNameTransformer::transform($ns);

        return $this
            ->http()
            ->get("/api/namespaces/{$ns}/releases/{$release}", ['info' => $info])
            ->json($key);
    }

    /**
     * /api/namespaces/:namespace/releases/:release?chart=<chartName>
     *
     * @see https://github.com/opskumu/helm-wrapper/blob/master/README_CN.md
     *
     * @author duc <1025434218@qq.com>
     * @param $ns
     * @param $release
     * @param ChartValuesImp $imp
     * @param string $jsonKey
     * @return bool
     * @throws \Exception
     */
    public function upgrade($ns, $release, ChartValuesImp $imp, $jsonKey = 'data')
    {
        $this->repoUpdate();
        $nsCopy = $ns;
        $ns = NsNameTransformer::transform($ns);
        $chart = $imp->getChart();

        $env = $imp->transformEnv();
        logger()->debug('env', $env);

        $wildcardHost = Str::after(parse_url(config('k8s.wildcard_domain', ''))['path'], '*');
        if ($wildcardHost) {
            $host = "{$release}-{$nsCopy}{$wildcardHost}";
            $secretName = "{$release}-{$nsCopy}-tls";
            $ingressConfig = [
                'ingress.enabled=true',
                "ingress.hosts[0].host=${host}",
                'ingress.hosts[0].paths[0]=/',
                "ingress.tls[0].secretName={$secretName}",
                "ingress.tls[0].hosts[0]=${host}",
                "ingress.annotations.kubernetes\.io\/ingress\.class=nginx",
                "ingress.annotations.cert\-manager\.io\/cluster\-issuer=letsencrypt\-prod",
            ];
        } else {
            $ingressConfig = [];
        }

        $imagePullSecrets = [];
        if ($secretNames = $imp->getImagePullSecrets()) {
            foreach ($secretNames as $key => $secretName) {
                $imagePullSecrets[] = "imagePullSecrets[{$key}].name=" . $secretName;
            }
        }

        $params = [
            'atomic'  => false,
            'install' => true,
            'set'     => [
                'image.pullPolicy=IfNotPresent',
                'image.repository=' . $imp->getRepository(),
                'image.tag=' . $imp->getTag(),
                ...$imagePullSecrets,
                ...$ingressConfig,
                ...$imp->getDefaultValues(),
            ],
        ];
        $params = array_merge_recursive($params, $env);
        Log::debug('helm upgrade array_merge_recursive', $params);

        if ($version = $imp->getChartVersion()) {
            $params['version'] = $version;
        }

        if ($this->hasRelease($ns, $release)) {
            $res = $this->http()->put("/api/namespaces/{$ns}/releases/{$release}?chart={$chart}", $params);
        } else {
            $res = $this->http()->post("/api/namespaces/{$ns}/releases/{$release}?chart={$chart}", $params);
        }

        if ($res->successful() && $res->json('code') == 0) {
            return $res->json($jsonKey);
        }

        throw new \Exception($res->body());
    }

    /**
     * /api/namespaces/:namespace/releases/:release
     *
     * @see https://github.com/opskumu/helm-wrapper/blob/master/README_CN.md
     *
     * @param $ns
     * @param $release
     *
     * @author duc <1025434218@qq.com>
     * @return bool
     * @throws \Exception
     */
    public function uninstall($ns, $release)
    {
        $ns = NsNameTransformer::transform($ns);
        if ($this->hasRelease($ns, $release)) {
            $res = $this->http()->delete("/api/namespaces/{$ns}/releases/{$release}");

            if ($res->successful() && $res->json('code') == 0) {
                return true;
            }

            throw new \Exception($res->body());
        }

        return true;
    }

    public function hasRelease($ns, $release)
    {
        if (in_array($release, Arr::pluck($this->list($ns, 'data', []), 'name'))) {
            return true;
        }

        return false;
    }

    /**
     * [PUT] /api/repositories
     *
     * @author duc <1025434218@qq.com>
     */
    public function repoUpdate()
    {
        $res = $this->http()->put('/api/repositories');
        if ($res->successful() && $res->json('code') == 0) {
            return true;
        }

        throw new \Exception($res->body());
    }

    public function addRepo($name, $url)
    {
        if (! $name || ! $url) {
            return;
        }
        $this->repoUpdate();
        $res = $this->http()->post('/api/repositories/charts', ['name' => $name, 'url' => $url]);
        if ($res->successful() && $res->json('code') == 0) {
            return true;
        }

        throw new \Exception($res->body());
    }

    public function uploadChart($chartData, $name)
    {
        $res = $this->http()
            ->attach('chart', $chartData, $name)
            ->post('/api/charts/upload');

        return $res->successful();
    }

    public function http()
    {
        return Http::baseUrl($this->domain);
    }
}
