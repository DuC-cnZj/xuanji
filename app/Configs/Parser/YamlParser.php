<?php

namespace App\Configs\Parser;

use Symfony\Component\Yaml\Yaml;

class YamlParser implements ParserImp
{
    protected string $valueKey;
    protected string $env;

    public function __construct(string $valueKey, string $env)
    {
        $this->valueKey = $valueKey;
        $this->env = $env;
    }

    public function parse(): array
    {
        $envArr = Yaml::parse($this->env) ?? [];

        return collect($envArr)
            ->map(fn ($item, $key) => $this->valueKey . '.' . $key . '=' . str_replace(',', '\,', $item))
            ->values()
            ->toArray();
    }
}
