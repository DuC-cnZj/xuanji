<?php

namespace App\Configs;

interface ChartValuesImp
{
    public function setEnv(string $env):ChartValuesImp;

    public function setIsSimpleEnv(bool $is):ChartValuesImp;

    public function setChartVersion(string $version):ChartValuesImp;

    public function setEnvFileType(string $type):ChartValuesImp;

    public function setEnvValuesPrefix(?string $prefix): ChartValuesImp;

    public function setRepository(string $repository): ChartValuesImp;

    public function setImagePullSecrets(array $imagePullSecrets): ChartValuesImp;

    public function setTag(string $tag): ChartValuesImp;

    public function getIsSimpleEnv():bool;

    public function getEnv():string ;

    public function transformEnv():array ;

    public function getEnvValuesPrefix():string ;

    public function getRepository():string ;

    public function getImagePullSecrets(): array;

    public function getTag():string ;

    public function getImage():string ;

    public function getChart():string ;

    public function setChart(string $chart):ChartValuesImp ;

    public function setDefaultValues(?array $values):ChartValuesImp;

    public function getDefaultValues():array;

    public function getEnvFileType():string;

    public function getChartVersion():string;
}
