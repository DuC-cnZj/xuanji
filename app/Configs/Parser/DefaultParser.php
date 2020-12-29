<?php

namespace App\Configs\Parser;

class DefaultParser implements ParserImp
{
    protected $valueKey;
    protected $env;

    public function __construct($valueKey, $env)
    {
        $this->valueKey = $valueKey;
        $this->env = $env;
    }

    public function parse(): array
    {
        // TODO: Implement parse() method.
    }
}
