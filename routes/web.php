<?php

use App\Http\Controllers\ConfigTipsController;
use App\Models\Ns;
use App\Services\K8sApi;
use App\Services\HelmApi;
use App\Services\GitlabApi;
use Illuminate\Support\Arr;
use App\Services\FileParser;
use App\Models\ProjectConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use League\CommonMark\CommonMarkConverter;
use App\Http\Controllers\NamespacesController;
use App\Http\Controllers\Gitlab\CommitsController;
use App\Http\Controllers\Gitlab\BranchesController;
use App\Http\Controllers\Gitlab\ProjectsController;
use App\Http\Controllers\ProjectsController as K8sProjectsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/ping', fn () => 'pong')->name("ping");

Route::get('/test', function (GitlabApi $api, K8sApi $k8sApi, HelmApi $helm, FileParser $fileParser) {
    // dd($k8sApi->getSecret('default', 'docakersecret'));
    dd($k8sApi->createSecret('default', 'duc-docker-secret', '18888780080', 'duc123243', 'registry.cn-hangzhou.aliyuncs.com'));
    $commit = app(GitlabApi::class)->commit(21691927, '7096069bad2a97bca24634e0b165b01761a15353');
    dd($commit);
    dd($fileParser->parseYaml(file_get_contents('../.xuanji.yaml')));
    // [$valid, $invalid] = collect($api->membershipProjects())
    //     ->map(function ($project) use ($api, $fileParser) {
    //         $file = $api->getProjectFile($project['id'], 'master', '.xuanji.yaml');

    //         if (! $file) {
    //             return [];
    //         }

    //         $yaml = $fileParser->parseYaml($file);
    //         if (Arr::has($yaml, ['config_file', 'repository', 'chart', 'helm_repo_name', 'helm_repo_url', 'default_values', 'tag_format', 'branches'])) {
    //             return array_merge($yaml, ['valid' => true, 'project_id' => $project['id']]);
    //         }

    //         return array_merge($yaml, ['valid' => false, 'project_id' => $project['id']]);
    //     })
    //     ->filter()
    //     ->partition(fn ($item) => $item['valid']);

    // $valid->each(fn ($item) => ProjectConfig::sync($item));

    // dd($valid, $invalid);
    // $res = collect($api->membershipProjects())->map(function ($project) use ($api, $fileParser) {
    //     $file = $api->getProjectFile($project['id'], 'master', '.xuanji.yaml');
    //     if (! $file) {
    //         return [];
    //     }

    //     $yaml = $fileParser->parseYaml($file);
    //     if (Arr::has($yaml, ['config_file', 'repository', 'chart', 'helm_repo_name', 'helm_repo_url', 'default_values', 'tag_format', 'branches'])) {
    //         return array_merge($yaml, ['valid' => true]);
    //     }

    //     return array_merge($yaml, ['valid' => false]);
    // })->filter()->values()->toArray();
    // dd($res);
    // dd($api->pipeline('21246576', 'master', 'fdfd22953466be8882bfa5494a999786e196ffb1'));
//    $ns = Ns::all();
//    dd($ns->toArray());
//    $api->getProjectFile(21246576, 'master', 'main.goaa');
//    dd($api->groupProjects('duccnzj'));
    // dd($helm->list('duc'));
//    $helm->upgrade('duc', 'sso');
//    $helm->uninstall('duc', 'sso');
//    $helm->get('duc', 'sso-demo');
//    $helm->list('duc');
//    $k8sApi->watchDeploy();
    // $data = $k8sApi->getPods('duc', 'app.kubernetes.io/instance=sso-demo', 'items');

    // $res = collect($data)->map(function ($item) {
//     return [
//         'name' => $item['metadata']['name'],
//         'labels' => $item['metadata']['labels'],
//         'created_at' => $item['metadata']['creationTimestamp'],
//         'images' => Arr::pluck($item['spec']['containers'], 'image'),
//         'status' => $item['status']['phase'],
//     ];
    // });
    // dd($res->toArray(), $data);
    // $res = $k8sApi->topNsPods('duc', 'items');
    // $cpu = collect($res)
    //     ->pluck('containers.*.usage.cpu')
    //     ->flatten()
    //     ->map(fn($value)=>rtrim($value, "n"))
    //     ->sum() / (1000*1000*1000);
    // // n (1m = 1000*1000n )：
    // dd($cpu);
    dd($k8sApi->getPod('devops-aaa', 'k8s-demo-laravel-laravel-workflow-cc5f7c76d-x94cx'));
//    $k8sApi->getPods("duc", [
//        'app.kubernetes.io/name'       => 'sso-chart',
//        'app.kubernetes.io/instance' => 'sso',
//    ]);
// $res = $k8sApi->getServices('duc', 'items');

// $nodePorts = collect($res)
//     ->filter(function ($item) {
//         return !!Arr::get($item, 'status.loadBalancer');
//     })
//     ->map(function ($item) {
//         return 'http://'.Arr::get($item, 'status.loadBalancer.ingress.0.hostname').":".Arr::get($item, 'spec.ports.0.nodePort');
//     })
//     ->values()
//     ->toArray();
// $res = $k8sApi->getIngress('duc', 'items.*.spec');
// $https = collect($res)->pluck("tls.*.hosts")->flatten()->filter()->values()->map(fn($item)=>'http://'.$item)->toArray();
// $http = collect($res)->pluck("rules.*.host")->flatten()->values()->map(fn($item)=>'https://'.$item)->toArray();
// dd(array_merge($nodePorts, $https, $http));
//     dd(collect($res)->filter(function ($item) {
//         return !!Arr::get($item, 'status.loadBalancer');
//     })
//     ->map(function ($item) {
//         return 'http://'.Arr::get($item, 'status.loadBalancer.ingress.0.hostname').":".Arr::get($item, 'spec.ports.0.nodePort');
//     })
//     ->values()
//     ->toArray()
// );
    // $res = $k8sApi->getPods('duc', [
    //     'app.kubernetes.io/name'     => 'sso-demo-chart',
    //     'app.kubernetes.io/instance' => 'sso-demo',
    // ], 'items.*.metadata.name');

    // $log = collect($k8sApi->getPodStatus('duc', $res[2], 'status.containerStatuses.*.ready'))->contains(false);
    // dd($log);
    // $log = $k8sApi->podLog('duc', Arr::last($res));
    // $md = new CommonMarkConverter([
    //     'html_input'         => 'strip',
    //     'allow_unsafe_links' => false,
    // ]);
    // dd([$res, $md->convertToHtml("```shell \n ${log} \n```")]);

    // return $md->convertToHtml("```shell \n ${log} \n```");

    // return $log;
    // dd($log);
//    $k8sApi->getDeploymentPods("duc", "sso-demo-sso-chart");
//    $k8sApi->getDeployments('duc');
//    dd(\Http::delete('http://127.0.0.1:9999/api/namespaces/test/releases/sso')->json());
//    dd(\Http::get('http://127.0.0.1:9999/api/namespaces/test/releases')->json());
//    return $k8sApi->getNamespaces('test');
});

Route::group([
   'middleware' => config('app.sso_enabled') ? 'sso.auth' : 'auth.simple',
], function () {
    Route::get('/config_tips', [ConfigTipsController::class, 'getLast']);

    Route::post('/config_tips', [ConfigTipsController::class, 'store']);

    // home page
    Route::get('/', [Controller::class, 'home']);

    // logout
    Route::post('/logout', [Controller::class, 'logout']);

    // 获取 k8s namespace 列表
    Route::get('/namespaces', [NamespacesController::class, 'index']);

    // 获取 k8s namespace 详情
    Route::get('/namespaces/{namespace}', [NamespacesController::class, 'show']);

    // 创建 namespace
    Route::post('/namespaces', [NamespacesController::class, 'store']);

    // 删除空间
    Route::delete('/namespaces/{namespace}', [NamespacesController::class, 'destroy']);

    // 空间下的 ingress 和 nodeport
    Route::get('/namespaces/{namespace}/namespaces_get_external_ips', [NamespacesController::class, 'getExternalIps']);

    // namespace 内存和cpu 使用情况
    Route::get('/namespaces/{namespace}/usage', [NamespacesController::class, 'usage']);

    // 空间下的项目列表
    Route::get('/namespaces/{namespace}/projects', [K8sProjectsController::class, 'index']);

    // 部署一个项目
    Route::post('/namespaces/{namespace}/projects', [K8sProjectsController::class, 'deploy']);

    // 更新项目
    Route::put('/namespaces/{namespace}/projects/{project}', [K8sProjectsController::class, 'update']);

    // 删除项目
    Route::delete('/namespaces/{namespace}/projects/{project}', [K8sProjectsController::class, 'uninstall']);

    // 获取项目详情
    Route::get('/namespaces/{namespace}/projects/{project}', [K8sProjectsController::class, 'show']);

    // 获取容器日志
    Route::get('/namespaces/{namespace}/projects/{project}/logs', [K8sProjectsController::class, 'containerLogs']);

    // 获取单个容器日志
    Route::get('/namespaces/{namespace}/projects/{project}/logs/{pod}', [K8sProjectsController::class, 'showLog']);

    // 获取 gitlab projects
    Route::get('/gitlab/projects', [ProjectsController::class, 'index']);

    Route::post('/gitlab/projects/sync', [ProjectsController::class, 'sync']);

    // 获取 gitlab project 的 commits 近一个月
    Route::get('/gitlab/projects/{project}/commits', [CommitsController::class, 'index']);

    // 获取 gitlab branches
    Route::get('/gitlab/projects/{project}/branches', [BranchesController::class, 'index']);

    // 获取 gitlab branch 的文件内容
    Route::get('/gitlab/projects/{project}/branches/{branch}/file', [ProjectsController::class, 'file']);

    // 获取 gitlab branch 的 commits
    Route::get('/gitlab/projects/{project}/branches/{branch}/commits', [BranchesController::class, 'commits']);

    // 获取 gitlab branch commit 的 最后一次 pipeline 信息
    Route::get('/gitlab/projects/{project}/branches/{branch}/commits/{commit}/pipeline', [CommitsController::class, 'pipeline']);
});
