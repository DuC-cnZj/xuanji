<?php

namespace App\Services\Images;

use Illuminate\Support\Str;

class DockerImageManager
{
    public function search(string $image): DockerImageInterface
    {
        if (Str::contains($image, 'aliyuncs.com')) {
            return (new Aliyun('', '', ''))->login()->search($image);
        }
    }
}
