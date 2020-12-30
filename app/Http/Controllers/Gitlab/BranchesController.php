<?php

namespace App\Http\Controllers\Gitlab;

use Carbon\Carbon;
use App\Services\GitlabApi;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\ProjectConfig;
use App\Http\Controllers\Controller;

class BranchesController extends Controller
{
    public function index(GitlabApi $api, $project)
    {
        $branchPatterns = ProjectConfig::whereProjectId($project)->value('branches');

        return collect($api->branches($project))
            ->filter(fn ($branch) => Str::is($branchPatterns, $branch['name']))
            ->values()
            ->toArray();
    }

    public function commits(GitlabApi $api, $project, $branch)
    {
        return collect($api->branchCommits($project, $branch))
            ->map(function ($item) {
                $date = Carbon::parse(Arr::get($item, 'committed_date'))->diffForHumans();

                return [
                    'id'  => Arr::get($item, 'id'),
                    'msg' => sprintf('[%s at %s]: %s', Arr::get($item, 'author_name'), $date, Arr::get($item, 'short_id') . '-' . Arr::get($item, 'title')),
                ];
            });
    }
}
