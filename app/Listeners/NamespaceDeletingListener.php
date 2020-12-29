<?php

namespace App\Listeners;

use App\Services\K8sApi;
use App\Events\NamespaceDeleting;
use Illuminate\Support\Facades\Log;

class NamespaceDeletingListener
{
    /**
     * @var K8sApi
     */
    private K8sApi $api;

    public function __construct(K8sApi $api)
    {
        $this->api = $api;
    }

    public function handleSecrets(NamespaceDeleting $event)
    {
        $namespace = $event->namespace;
        foreach ($namespace->image_pull_secrets ?? [] as $secretName) {
            $this->api->deleteSecret($namespace->name, $secretName);
        }
        Log::debug("handleSecrets del {$namespace->name}");
    }

    public function handleUninstallProjects(NamespaceDeleting $event)
    {
        $namespace = $event->namespace;
        $namespace->projects->each(fn ($item) => $namespace->uninstall($item));
        Log::debug("handleUninstallProjects {$namespace->name}");
    }
}
