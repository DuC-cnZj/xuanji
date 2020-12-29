<?php

namespace App\Http\Controllers\Gitlab;

use App\Services\GitlabApi;
use Illuminate\Support\Arr;
use App\Models\ProjectConfig;
use App\Http\Controllers\Controller;

class ProjectsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param GitlabApi $api
     * @return array
     */
    public function index(GitlabApi $api)
    {
        $projectIds = ProjectConfig::query()->pluck('project_id')->toArray();

        return collect($api->membershipProjects())
            ->map(function ($item) {
                return [
                    'id'                  => Arr::get($item, 'id'),
                    'name'                => Arr::get($item, 'name'),
                    'name_with_namespace' => Arr::get($item, 'name_with_namespace'),
                    'ssh_url_to_repo'     => Arr::get($item, 'ssh_url_to_repo'),
                    'http_url_to_repo'    => Arr::get($item, 'http_url_to_repo'),
                    'avatar_url'          => Arr::get($item, 'avatar_url'),
                    'path_with_namespace' => Arr::get($item, 'path_with_namespace'),
                ];
            })
            ->filter(fn ($item) => in_array($item['id'], $projectIds))
            ->values()
            ->toArray();
    }

    public function file(int $project, string $branch, GitlabApi $api)
    {
        $content = '# can not fetch default config file.';

        /** @var ProjectConfig $pc */
        $pc = ProjectConfig::query()
            ->where('project_id', $project)
            ->first(['config_file', 'config_file_type']);

        if ($pc->hasConfigFile()) {
            $content = $api->getProjectFile($project, $branch, $pc->configFileName()) ?: $content;
        }

        return [
            'content' => $content,
            'type'    => $pc->config_file_type,
        ];
    }

    /**
     * 获取 gitlab 下的配置了 .xuanji.yaml 的 project
     *
     * @param GitlabApi $api
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(GitlabApi $api)
    {
        [$valid, $invalid] = $api->syncProjectConfig();

        return response()->json([
            'success'  => true,
            'imported' => $valid->count(),
            'invalid'  => $invalid->map->projectId()->implode(', '),
        ], 201);
    }
}
