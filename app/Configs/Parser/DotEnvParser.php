<?php

namespace App\Configs\Parser;

use App\Services\FileParser;

class DotEnvParser implements ParserImp
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
        $envArr = FileParser::parseDotEnv($this->env);

        return collect($envArr)
            ->map(fn ($item, $key) => $this->valueKey . '.' . $key . '=' . $item)
            ->values()
            ->toArray();
    }
}
