<?php

namespace App\Services;

use Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

class FileParser
{
    public static function parseDotEnv(string $content, $key = null, $default = null)
    {
        try {
            $data = Dotenv::parse($content);
        } catch (\Exception $e) {
            report($e);

            return [];
        }

        if ($key) {
            return data_get($data, $key, $default);
        }

        return $data;
    }

    public static function parseYaml(string $content, $key = null, $default = null)
    {
        try {
            $data = Yaml::parse($content);
        } catch (\Exception $e) {
            report($e);

            return [];
        }
        if ($key) {
            return data_get($data, $key, $default);
        }

        return $data;
    }
}
