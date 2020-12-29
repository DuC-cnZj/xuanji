<?php

namespace App\Services\Images;

interface DockerImageInterface
{
    public function login(): DockerImageInterface;

    public function search(string $image): DockerImageInterface;

    public function exists(): bool;

    public function getRepoInfo(): array;

    public function getTagInfo(): array;
}
