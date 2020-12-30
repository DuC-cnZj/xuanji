<?php

namespace App\Configs;

use App\Services\GitlabApi;
use Illuminate\Support\Arr;
use App\Services\FileParser;
use App\Models\ProjectConfig;

class XuanjiYaml
{
    protected int $projectId;
    protected bool $valid = false;
    protected bool $shouldImport = false;
    protected array $data = [];
    protected GitlabApi $api;
    protected FileParser $parser;
    protected array $chartMustHave = ['repository', 'tag_format', 'chart', 'helm_repo_name', 'helm_repo_url'];
    protected array $localChartMustHave = ['local_chart', 'repository', 'tag_format'];

    public function __construct($projectInfo)
    {
        $this->projectId = $projectInfo['id'];
        $this->api = app(GitlabApi::class);
        $this->parser = app(FileParser::class);
    }

    public function check()
    {
        $file = $this->api->getProjectFile($this->projectId, 'master', '.xuanji.yaml');

        $yaml = $this->parser->parseYaml($file);
        if (! $yaml) {
            return;
        }
        $this->shouldImport = true;

        $chartConfigured = false;
        if (Arr::has($yaml, 'local_chart')) {
            if (!$this->ensureHasFields($yaml, $this->localChartMustHave)) {
                return;
            }
            $chartConfigured = true;
        }

        if (Arr::has($yaml, 'chart')) {
            if (!$this->ensureHasFields($yaml, $this->chartMustHave) && !$chartConfigured) {
                return;
            }
        }

        $this->valid = true;

        $this->data = array_merge($yaml, [
            'chart'          => Arr::get($yaml, 'chart', ''),
            'local_chart'    => Arr::get($yaml, 'local_chart', ''),
            'valid'          => true,
            'project_id'     => $this->projectId,
            'chart_version'  => Arr::get($yaml, 'chart_version', ''),
            'helm_repo_name' => Arr::get($yaml, 'helm_repo_name', ''),
            'helm_repo_url'  => Arr::get($yaml, 'helm_repo_url', ''),
            'config_field'   => Arr::get($yaml, 'config_field', 'config'),
            'branches'       => Arr::get($yaml, 'branches', ['*']),
            'is_simple_env'  => Arr::get($yaml, 'is_simple_env', true),
            'default_values' => Arr::get($yaml, 'default_values', []),
        ]);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function shouldImport(): bool
    {
        return $this->shouldImport;
    }

    public function projectId()
    {
        return $this->projectId;
    }

    private function ensureHasFields($yaml, $keys): bool
    {
        return Arr::has($yaml, $keys);
    }

    public function data(): array
    {
        return $this->data;
    }

    public function sync()
    {
        if ($this->data) {
            ProjectConfig::sync($this->data());
        }
    }
}
