<?php

namespace App\Http\Controllers;

use App\Models\Ns;
use App\Models\Project;
use App\Services\K8sApi;
use App\Services\HelmApi;
use App\Services\GitlabApi;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProjectConfig;
use App\Configs\ChartValuesImp;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;

class ProjectsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Ns $namespace
     * @return \Illuminate\Http\Response
     */
    public function index(Ns $namespace)
    {
        return $namespace->projects->toArray();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Ns $namespace
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    public function deploy(Ns $namespace, Request $request)
    {
        $project = Str::slug($request->project);
        $projectId = $request->project_id;
        $branch = $request->branch;
        $commit = $request->commit;
        $env = $request->env ?? '';

        /** @var ProjectConfig $config */
        $config = ProjectConfig::query()->where('project_id', $projectId)->first();

        $this->upgradeOrInstall($config, $projectId, $branch, $commit, $namespace, $env, $project);

        return tap(
            $namespace->projects()->firstOrNew(['project_id' => $projectId]),
            fn ($p) => $p->fill([
                'name'            => $project,
                'branch'          => $branch,
                'commit'          => $commit,
                'env'             => $env,
                'config_snapshot' => $config->toArray(),
                'creator'         => auth()->user()->user_name,
            ])->save()
        );
    }

    public function show($namespace, Project $project)
    {
        return $project->detail();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Ns $namespace
     * @param \Illuminate\Http\Request $request
     * @param Project $project
     * @param ChartValuesImp $imp
     * @param HelmApi $helmApi
     * @param GitlabApi $gitlabApi
     * @return array
     * @throws \Exception
     */
    public function update(Ns $namespace, Request $request, Project $project, ChartValuesImp $imp, HelmApi $helmApi, GitlabApi $gitlabApi)
    {
        $branch = $project->branch;
        $commit = $project->commit;
        $projectId = $project->project_id;
        $env = $request->env ?? '';
        /** @var ProjectConfig $config */
        $config = ProjectConfig::query()->where('project_id', $projectId)->first();
        $config->refreshRepo();
        Log::debug('repo', [$config->helm_repo_name, $config->helm_repo_url]);

        $this->upgradeOrInstall($config, $projectId, $branch, $commit, $namespace, $env, $project->name);

        $project->update([
            'env'             => $imp->getEnv(),
            'config_snapshot' => $config->toArray(),
        ]);

        return $project->toArray();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Ns $namespace
     * @param Project $project
     * @return \Illuminate\Http\Response
     */
    public function uninstall(Ns $namespace, Project $project)
    {
        $namespace->uninstall($project);

        return response()->noContent(204);
    }

    public function containerLogs(Request $request, Ns $namespace, Project $project, K8sApi $k8sApi)
    {
        $log = '';
        $current = $request->current;

        $pods = $k8sApi->getPods($namespace->name, null, 'items.*.metadata');

        $pods = collect($pods)
            ->filter(fn ($data) => $data['ownerReferences'][0]['kind'] != 'Job' && Str::contains($data['name'], $project->name))
            ->pluck('name')
            ->toArray();

        $res = collect($pods)
            ->map(
                fn ($pod) => collect($k8sApi->getPod($namespace->name, $pod, 'spec.containers.*.name'))->map(fn ($c) => [
                    'name'           => $pod,
                    'container'      => $c,
                    'ready'          => $k8sApi->isPodReady($namespace->name, $pod),
                    'container_name' => "{$pod}::{$c}",
                ])->toArray()
            )->flatten(1)->values();

        if ($pods) {
            if (! ($current && $res->pluck('container_name')->contains($current))) {
                $current = Arr::first($res)['container_name'];
            }
            [$pod, $container] = explode('::', $current);
            $log = $k8sApi->getPodLog($namespace->name, $pod, $container);
        }

        return [
            'containers' => $res->toArray(),
            'current'    => $current,
            'log'        => $this->renderLogHtml($log),
            'ready'      => $current ? $k8sApi->isPodReady($namespace->name, $current) : '',
            'all_ready'  => ! $res->contains('ready', false),
        ];
    }

    public function showLog(Request $request, Ns $namespace, $project, $pod, K8sApi $k8sApi)
    {
        $log = $k8sApi->getPodLog($namespace->name, $pod, $request->input('container'));

        return ['log' => $this->renderLogHtml($log), 'ready' => $k8sApi->isPodReady($namespace->name, $pod)];
    }

    /**
     * @param string $log
     * @return string
     *
     * @author duc <1025434218@qq.com>
     */
    protected function renderLogHtml(string $log): string
    {
        return $this->getCommonMarkConverter()->convertToHtml(
            <<<LOG
```shell
${log}
```
LOG
        );
    }

    /**
     * @return CommonMarkConverter
     *
     * @author duc <1025434218@qq.com>
     */
    protected function getCommonMarkConverter(): CommonMarkConverter
    {
        return new CommonMarkConverter([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * @param ProjectConfig $config
     * @param $projectId
     * @param $branch
     * @param $commit
     * @param Ns $namespace
     * @param string $env
     * @param string $projectName
     * @throws \Exception
     *
     * @author duc <1025434218@qq.com>
     */
    private function upgradeOrInstall(ProjectConfig $config, $projectId, $branch, $commit, Ns $namespace, string $env, string $projectName): void
    {
        $gitlabApi = app(GitlabApi::class);
        $helmApi = app(HelmApi::class);
        $imp = app(ChartValuesImp::class);

        $installed = false;
        if ($config->preferLocalChart()) {
            $chartName = Str::of($config->local_chart)->explode('/')->last();

            try {
                $chartTgzData = $gitlabApi->getProjectFile($projectId, 'master', $config->local_chart);
                if ($chartTgzData) {
                    $ok = $helmApi->uploadChart($chartTgzData, $chartName);

                    if (! $ok) {
                        throw new \Exception("can't upload chart");
                    }

                    $imp
                        ->setChart($chartName)
                        ->setDefaultValues($config->default_values)
                        ->setTag($config->replaceVars($branch, $commit))
                        ->setRepository($config->repository)
                        ->setEnvFileType($config->config_file_type)
                        ->setEnvValuesPrefix($config->config_field)
                        ->setIsSimpleEnv($config->is_simple_env)
                        ->setImagePullSecrets($namespace->image_pull_secrets ?? [])
                        ->setEnv($env);

                    $helmApi->deploy($namespace->name, $projectName, $imp);
                    $installed = true;
                }
            } catch (\Exception $e) {
                logger()->error('error install use local_chart', [
                    'chartName'   => $chartName,
                    'local_chart' => $config->local_chart,
                ]);
            } finally {
                if (! $installed) {
                    if (! $config->chartConfigured()) {
                        throw new \Exception('chart 未配置');
                    }

                    $config->refreshRepo();

                    $imp
                        ->setChart($config->chart)
                        ->setChartVersion($config->chart_version)
                        ->setDefaultValues($config->default_values)
                        ->setTag($config->replaceVars($branch, $commit))
                        ->setRepository($config->repository)
                        ->setEnvFileType($config->config_file_type)
                        ->setEnvValuesPrefix($config->config_field)
                        ->setIsSimpleEnv($config->is_simple_env)
                        ->setImagePullSecrets($namespace->image_pull_secrets ?? [])
                        ->setEnv($env);

                    $helmApi->deploy($namespace->name, $projectName, $imp);
                }
            }
        }
    }
}
