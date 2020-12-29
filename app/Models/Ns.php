<?php

namespace App\Models;

use DateTimeInterface;
use Facades\App\Services\HelmApi;
use App\Transformers\NsNameTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ns extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'user_id', 'user_name', 'image_pull_secrets',
    ];

    protected $casts = [
        'image_pull_secrets' => 'array',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function uninstall(Project $project)
    {
        if (HelmApi::uninstall($this->name, $project->name)) {
            $project->delete();

            return true;
        }

        return false;
    }

    public function hasProject(string $project)
    {
        // todo 是否需要校验k8s api？
        return in_array($project, $this->projects->pluck('name')->toArray());
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = NsNameTransformer::transform($value);
    }

    public function getNameAttribute($value)
    {
        return NsNameTransformer::reset($value);
    }
}
