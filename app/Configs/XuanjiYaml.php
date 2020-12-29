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
    protected array $mustHave = ['config_file', 'config_file_type', 'repository', 'tag_format'];

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

        if (! $this->ensureHasFields($yaml, $this->mustHave)) {
            return;
        }
//        chart, local_chart 二选一
        if (! Arr::hasAny($yaml, ['chart', 'local_chart'])) {
            return;
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

    public function isValid()
    {
        return $this->valid;
    }

    public function shouldImport()
    {
        return $this->shouldImport;
    }

    public function projectId()
    {
        return $this->projectId;
    }

    private function ensureHasFields($yaml, $keys)
    {
        return Arr::has($yaml, $keys);
    }

    public function data()
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
