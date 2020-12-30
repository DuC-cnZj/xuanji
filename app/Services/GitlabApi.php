<?php

namespace App\Services;

use App\Configs\XuanjiYaml;
use Illuminate\Support\Facades\Http;

class GitlabApi
{
    protected $gitlabUrl;

    public function __construct()
    {
        $this->gitlabUrl = config('gitlab.url');
    }

    public function membershipProjects($key = null)
    {
        $res = $this->http()->get('api/v4/projects?membership=true');

        return $res->json($key);
    }

    public function groupProjects($group, $key = null)
    {
        $res = $this->http()->get("api/v4/groups/{$group}/projects");

        return $res->json($key);
    }

    public function branches($project, $key = null)
    {
        $res = $this->http()->get("api/v4/projects/{$project}/repository/branches/");

        return $res->json($key);
    }

    public function commits($project, $key = null)
    {
        $res = $this->http()
            ->get(
                "api/v4/projects/{$project}/repository/commits",
                [
                    'since' => today()->subMonth()->toIso8601String(),
                ]
            );

        return $res->json($key);
    }

    public function commit($project, $commit, $key = null)
    {
        $res = $this->http()->get("api/v4/projects/{$project}/repository/commits/{$commit}");

        return $res->json($key);
    }

    public function getProjectFile($project, $branch, $file)
    {
        $file = urlencode($file);

        $res = $this->http()->get("/api/v4/projects/{$project}/repository/files/{$file}?ref={$branch}");

        if ($res->successful()) {
            return base64_decode($res->json('content'));
        }

        logger()->error('getProjectFile', [
            'status' => $res->status(),
            'body'   => $res->body(),
            'file'   => $file,
        ]);

        return '';
    }

    public function branchCommits($project, $branch, $key = null)
    {
        $res = $this->http()
            ->get(
                "api/v4/projects/{$project}/repository/commits",
                [
                    'ref_name' => $branch,
                    'since'    => today()->subMonth()->toIso8601String(),
                ]
            );

        return $res->json($key);
    }

    public function pipeline(int $projectId, string $branch, string $sha, $key = null)
    {
        $res = $this->http()
            ->get("api/v4/projects/{$projectId}/pipelines", [
                'ref' => $branch,
                'sha' => $sha,
            ]);

        return $res->json($key);
    }

    public function lastPipelineStatus(int $projectId, string $branch, string $sha)
    {
        return $this->pipeline($projectId, $branch, $sha, '0.status');
    }

    public function syncProjectConfig(): array
    {
        [$valid, $invalid] = collect($this->membershipProjects())
            ->mapInto(XuanjiYaml::class)
            ->each
            ->check()
            ->filter
            ->shouldImport()
            ->partition
            ->isValid();

        $valid->each->sync();

        return [$valid, $invalid];
    }

    public function http()
    {
        return Http::withHeaders([
            'PRIVATE-TOKEN' => config('gitlab.token'),
        ])
            ->baseUrl($this->gitlabUrl);
    }
}
