<?php

namespace App\Models;

use Carbon\Carbon;
use App\Services\K8sApi;
use App\Services\GitlabApi;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'env', 'project_id', 'branch', 'commit', 'creator', 'config_snapshot'];

    protected $casts = [
        'config_snapshot' => 'array',
    ];

    public function namespace()
    {
        return $this->belongsTo(Ns::class, 'ns_id');
    }

    public function config()
    {
        return $this->hasOne(ProjectConfig::class, 'project_id', 'project_id');
    }

    public function getEnvFileTypeAttribute()
    {
        return data_get($this->config_snapshot, 'config_file_type', '');
    }

    public function detail()
    {
        $data = app(K8sApi::class)->getPods(
            $this->namespace->name,
            'app.kubernetes.io/instance=' . $this->name,
            'items'
        );
        $commit = app(GitlabApi::class)->commit($this->project_id, $this->commit);

        return [
            'commiter_name'  => $commit['committer_name'],
            'commiter_email' => $commit['committer_email'],
            'committed_date' => $commit['committed_date'],
            'web_url'        => $commit['web_url'],
            'title'          => $commit['title'],
            'creator'        => $this->creator,
            'pods'           => collect($data)->map(function ($item) {
                return [
                    'name'       => $item['metadata']['name'],
                    'labels'     => $item['metadata']['labels'],
                    'created_at' => Carbon::parse($item['metadata']['creationTimestamp'])->diffForHumans(),
                    'images'     => Arr::pluck($item['spec']['containers'], 'image'),
                    'status'     => $item['status']['phase'],
                    'ready'      => ! collect(Arr::pluck($item['status']['containerStatuses'], 'ready'))->contains(false),
                    'links'      => collect($this->getExternalIps())->flatten()->values()->toArray(),
                ];
            })->toArray(),
        ];
    }

    public function getExternalIps()
    {
        $k8sApi = app(K8sApi::class);
        $nodePorts = $this->nodePorts($k8sApi);

        [$https, $http] = $this->ingresses($k8sApi);

        return array_merge_recursive($nodePorts, $https, $http);
    }

    /**
     * @param K8sApi $k8sApi
     * @return array
     *
     * @author duc <1025434218@qq.com>
     */
    protected function nodePorts(K8sApi $k8sApi): array
    {
        $res = $k8sApi->getProjectServices($this, 'items');

        return collect($res)
            ->map
            ->filter(function ($item) {
                return Arr::get($item, 'spec.type') == 'NodePort' && count(Arr::get($item, 'spec.ports')) >= 1;
            })
            ->map
            ->map(function ($item) {
                $host = Arr::get($item, 'status.loadBalancer.ingress.0.hostname') ?? config('k8s.cluster_ip');

                $protocol = "http://";

                if (Str::contains(strtolower(Arr::get($item, 'spec.ports.0.name')), ['tcp', 'rpc', 'grpc'])) {
                    $protocol = "tcp://";
                }

                return $protocol . $host . ':' . Arr::get($item, 'spec.ports.0.nodePort');
            })
            ->toArray();
    }

    /**
     * @param K8sApi $k8sApi
     * @return array
     *
     * @author duc <1025434218@qq.com>
     */
    protected function ingresses(K8sApi $k8sApi): array
    {
        $res = $k8sApi->getProjectIngress($this, 'items.*.spec');
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

    public function getAllPodReadyAttribute()
    {
        return $this->allPodReady();
    }

    public function allPodReady(): bool
    {
        /** @var K8sApi $k8sApi */
        $k8sApi = app(K8sApi::class);
        $pods = collect($k8sApi->getPods($this->namespace->name, null, 'items.*.metadata'))
            ->filter(fn ($data) => $data['ownerReferences'][0]['kind'] != 'Job' && Str::contains($data['name'], $this->name))
            ->pluck('name')
            ->toArray();

        $res = collect($pods)
            ->map(
                fn ($pod) => collect($k8sApi->getPod($this->namespace->name, $pod, 'spec.containers.*.name'))->map(fn ($c) => [
                    'name'           => $pod,
                    'container'      => $c,
                    'ready'          => $k8sApi->isPodReady($this->namespace->name, $pod),
                    'container_name' => "{$pod}::{$c}",
                ])->toArray()
            )->flatten(1)->values();

        return ! $res->contains('ready', false);
    }
}
