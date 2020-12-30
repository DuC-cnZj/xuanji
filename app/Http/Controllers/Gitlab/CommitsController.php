<?php

namespace App\Http\Controllers\Gitlab;

use Carbon\Carbon;
use App\Services\GitlabApi;
use Illuminate\Support\Arr;
use App\Http\Controllers\Controller;

class CommitsController extends Controller
{
    public function index(GitlabApi $api, $project)
    {
        return collect($api->commits($project))->map(function ($item) {
            $date = Carbon::parse(Arr::get($item, 'committed_date'))->diffForHumans();

            return [
                'id'  => Arr::get($item, 'id'),
                'msg' => sprintf('[%s at %s]: %s', Arr::get($item, 'author_name'), $date, Arr::get($item, 'short_id') . '-' . Arr::get($item, 'title')),
            ];
        });
    }

    public function pipeline($project, $branch, $commit, GitlabApi $api)
    {
        return $api->pipeline($project, $branch, $commit);
    }
}
