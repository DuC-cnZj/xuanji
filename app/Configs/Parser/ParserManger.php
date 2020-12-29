<?php

namespace App\Configs\Parser;

class ParserManger
{
    public function make($valueKey, $env, $fileType = 'yaml'): ParserImp
    {
        switch ($fileType) {
            case 'env':
                return new DotEnvParser($valueKey, $env);
            case 'yaml':
                return new YamlParser($valueKey, $env);
            case 'php':
            default:
                return new DefaultParser($valueKey, $env);
        }
    }
}
