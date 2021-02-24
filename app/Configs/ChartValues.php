<?php

namespace App\Configs;

use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;
use App\Configs\Parser\ParserManger;

class ChartValues implements ChartValuesImp
{
    protected string $tag;
    protected string $env;
    protected string $chart = '';
    protected string $chartVersion = '';
    protected bool $isSimpleEnv = true;
    protected string $repository = '';
    protected string $envValuesPrefix = 'env';
    protected string $envFileType = '.env';
    protected string $tagDefault = 'latest';
    protected string $pullPolicy = 'IfNotPresent';
    protected array $values = [];
    protected array $imagePullSecrets = [];

    protected ParserManger $parser;

    public function __construct(ParserManger $parser)
    {
        $this->parser = $parser;
    }

    public function setRepository(string $repository): ChartValuesImp
    {
        $this->repository = $repository;

        return $this;
    }

    public function setTag(string $tag): ChartValuesImp
    {
        $this->tag = $tag;

        return $this;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }

    /**
     * @return array
     */
    public function getImagePullSecrets(): array
    {
        return $this->imagePullSecrets;
    }

    /**
     * @param array $imagePullSecrets
     * @return ChartValues
     */
    public function setImagePullSecrets(array $imagePullSecrets): ChartValuesImp
    {
        $this->imagePullSecrets = $imagePullSecrets;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTag(): string
    {
        return $this->tag ?? $this->tagDefault;
    }

    public function getImage(): string
    {
        return sprintf('%s:%s', $this->getRepository(), $this->getTag());
    }

    public function setIsSimpleEnv(bool $is):ChartValuesImp
    {
        $this->isSimpleEnv = $is;

        return $this;
    }

    public function setEnvFileType(string $type):ChartValuesImp
    {
        if (! in_array($type, ['.env', 'yaml', 'env', 'php'])) {
            throw new \Exception('unsupport type ' . $type);
        }

        $this->envFileType = $type;

        return $this;
    }

    public function setEnv(string $env): ChartValuesImp
    {
        $this->env = $env;

        return $this;
    }

    public function getEnv(): string
    {
        return $this->env;
    }

    public function getChart():string
    {
        return $this->chart;
    }

    public function setChart(string $chart):ChartValuesImp
    {
        $this->chart = $chart;

        return $this;
    }

    public function setDefaultValues(?array $values):ChartValuesImp
    {
        $this->values = $values ?? [];

        return $this;
    }

    public function getDefaultValues():array
    {
        return $this->values;
    }

    public function setEnvValuesPrefix(?string $prefix): ChartValuesImp
    {
        $this->envValuesPrefix = $prefix ?: $this->envValuesPrefix;

        return $this;
    }

    public function getEnvValuesPrefix():string
    {
        return $this->envValuesPrefix;
    }

    public function getIsSimpleEnv():bool
    {
        return $this->isSimpleEnv;
    }

    public function getEnvFileType():string
    {
        return $this->envFileType;
    }

    public function setChartVersion(string $version): ChartValuesImp
    {
        $this->chartVersion = $version;

        return $this;
    }

    public function getChartVersion(): string
    {
        return $this->chartVersion;
    }

    public function transformEnv(): array
    {
        if ($this->getIsSimpleEnv()) {
            $data = [];
            Arr::set($data, $this->getEnvValuesPrefix(), $this->getEnv());

            return ['values' => Yaml::dump($data)];
        }
        $data = $this->parser
            ->make($this->getEnvValuesPrefix(), $this->getEnv(), $this->getEnvFileType())
            ->parse();

        return ['set' => $data];
    }
}
