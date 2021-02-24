<?php

namespace App\Events;

use App\Models\Ns;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class NamespaceCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Ns $namespace;

    public function __construct(Ns $namespace)
    {
        $this->namespace = $namespace;
    }
}
