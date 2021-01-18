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
     * @return array
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
     * @return array
     * @throws \Exception
     */
    public function update(Ns $namespace, Request $request, Project $project, ChartValuesImp $imp)
    {
        $input = $this->validate($request, [
            'branch' => 'required',
            'commit' => 'required',
            'env'    => 'string',
        ]);

        $branch = $input['branch'];
        $commit = $input['commit'];
        $env = $input['env'];

        /** @var ProjectConfig $config */
        $config = $project->config;
        $projectId = $project->project_id;

        $this->upgradeOrInstall($config, $projectId, $branch, $commit, $namespace, $env, $project->name);

        $project->update([
            'branch'          => $input['branch'],
            'commit'          => $input['commit'],
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

        return response()->noContent();
    }

    public function containerLogs(Request $request, Ns $namespace, Project $project, K8sApi $k8sApi)
    {
        $log = '';
        $current = $request->current;

        $pods = $project->podNamesWithoutJob();

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
     * @return mixed
     * @throws \Exception
     *
     * @author duc <1025434218@qq.com>
     */
    private function upgradeOrInstall(ProjectConfig $config, $projectId, $branch, $commit, Ns $namespace, string $env, string $projectName)
    {
        $gitlabApi = app(GitlabApi::class);
        $helmApi = app(HelmApi::class);
        $imp = app(ChartValuesImp::class);

        $chartName = Str::of($config->local_chart)->explode('/')->last();

        if (! $chartTgzData = $gitlabApi->getProjectFile($projectId, $branch, $config->local_chart)) {
            throw new \Exception(sprintf('tgz not found in project: %d, branch %s, path %s.', $projectId, $branch, $config->local_chart));
        }

        if (! $helmApi->uploadChart($chartTgzData, $chartName)) {
            throw new \Exception("can't upload chart");
        }

        if ($config->preferLocalChart()) {
            try {
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

                return $helmApi->deploy($namespace->name, $projectName, $imp);
            } catch (\Exception $e) {
                Log::info($e->getMessage(), [$e]);
            }
        }

        if (! $config->chartConfigured()) {
            throw new \Exception(sprintf('chart name: %s, local_chart: %s, error: %s.', $chartName, $config->local_chart, $e->getMessage()));
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

        return $helmApi->deploy($namespace->name, $projectName, $imp);
    }
}
