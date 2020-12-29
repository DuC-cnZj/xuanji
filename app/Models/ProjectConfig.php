<?php

namespace App\Models;

use App\Services\HelmApi;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'config_file',
        'chart_version',
        'config_file_type',
        'repository',
        'chart',
        'local_chart',
        'helm_repo_name',
        'helm_repo_url',
        'default_values',
        'tag_format',
        'branches',
        'config_field',
        'is_simple_env',
    ];

    protected $casts = [
        'default_values' => 'array',
        'branches'       => 'array',
        'is_simple_env'  => 'bool',
    ];

    public static function sync(array $config)
    {
        $cfg = static::query()->firstOrNew(['project_id' => $config['project_id']]);
        $cfg->fill($config)->save();

        return $cfg;
    }

    public function replaceVars($branch, $commit):string
    {
        return Str::replaceFirst('$commit', $commit, Str::replaceFirst('$branch', $branch, $this->tag_format));
    }

    public function refreshRepo()
    {
        app(HelmApi::class)->addRepo($this->helm_repo_name, $this->helm_repo_url);
    }

    public function hasConfigFile(): bool
    {
        return (bool) $this->config_file;
    }

    public function configFileName()
    {
        return $this->config_file;
    }

    public function preferLocalChart(): bool
    {
        return $this->local_chart && Str::endsWith($this->local_chart, '.tgz');
    }

    public function chartConfigured(): bool
    {
        return (bool) $this->chart;
    }
}
