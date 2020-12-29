<?php

namespace App\Events;

use App\Models\Ns;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class NamespaceDeleting
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Ns $namespace;

    public function __construct(Ns $ns)
    {
        $this->namespace = $ns;
    }
}
