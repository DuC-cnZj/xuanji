<?php

namespace App\Listeners;

use App\Services\K8sApi;
use App\Events\NamespaceCreated;
use Illuminate\Support\Facades\Log;

class NamespaceCreatedListener
{
    public K8sApi $api;

    public function __construct(K8sApi $api)
    {
        $this->api = $api;
    }

    public function handleSecrets(NamespaceCreated $event)
    {
        $name = $event->namespace->name;

        $dockerServer = config('k8s.docker_server');
        $dockerAuth = config('k8s.docker_auth');

        if ($dockerAuth) {
            $secretName = "{$name}-dockersecret";
            if ($this->api->hasSecret($name, $secretName)) {
                $this->api->deleteSecret($name, $secretName);
            }
            $this->api->createSecret($name, $secretName, $dockerAuth, $dockerServer);
            $fillData['image_pull_secrets'] = [$secretName];

            $event->namespace->update(['image_pull_secrets' => [$secretName]]);
            Log::debug("create ns {$name} secret: {$secretName}");
        }
    }
}
