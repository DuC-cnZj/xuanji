<?php

namespace App\Transformers;

use Illuminate\Support\Str;

class NsNameTransformer
{
    public static function transform($value)
    {
        $prefix = config('k8s.ns_prefix');
        if (! Str::startsWith($value, $prefix)) {
            $value = $prefix . $value;
        }

        return $value;
    }

    public static function reset($value)
    {
        $prefix = config('k8s.ns_prefix');

        return Str::after($value, $prefix);
    }
}
