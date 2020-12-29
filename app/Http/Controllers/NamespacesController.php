<?php

namespace App\Http\Controllers;

use App\Models\Ns;
use App\Services\K8sApi;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Events\NamespaceCreated;
use App\Events\NamespaceDeleting;
use Illuminate\Support\Facades\Log;

class NamespacesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param K8sApi $k8sApi
     * @return array
     */
    public function index(K8sApi $k8sApi)
    {
        [$owned, $others] = Ns::with('projects')
            ->get()
            ->map(function ($ns) use ($k8sApi) {
                return [
                    'owned'     => auth()->id() == $ns->user_id,
                    'id'        => $ns->id,
                    'namespace' => $ns->name,
                    'projects'  => $ns->projects->map->only('id', 'env', 'name', 'env_file_type', 'all_pod_ready')->toArray(),
                    'links'     => collect($ns->projects->map->getExternalIps())->flatten()->values()->toArray(),
                    'usage'     => $this->usage($ns, $k8sApi),
                ];
            })
            ->partition
            ->owned;

        return $owned->merge($others)->values()->toArray();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param K8sApi $api
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, K8sApi $api)
    {
        $this->validate($request, ['name' => ['required', 'regex:/^[a-z0-9]+[-]*[a-z0-9]*$/']], ['name.regex' => "e.g. 'my-name',  or '123-abc'"]);

        $name = $request->name;

        $ns = Ns::query()->firstOrNew(['name' => $name]);

        if ($ns->exists && $api->hasNamespace($name)) {
            return response()->json([], 201);
        }

        $api->createNamespaces($name);
        $ns->fill([
            'user_id'   => auth()->user()->id,
            'user_name' => auth()->user()->user_name,
        ])->save();

        NamespaceCreated::dispatch($ns);

        return response()->json([], 201);
    }

    public function show(Ns $namespace)
    {
        return $namespace->projects->map->detail()->toArray();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Ns $namespace
     * @param K8sApi $k8sApi
     * @return \Illuminate\Http\Response
     */
    public function destroy(Ns $namespace, K8sApi $k8sApi)
    {
        try {
            NamespaceDeleting::dispatch($namespace);
            $namespace->delete();
            $k8sApi->deleteNamespace($namespace->name);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->noContent();
    }

    public function usage(Ns $namespace, K8sApi $k8sApi)
    {
        $res = $k8sApi->topNsPods($namespace->name, 'items');

        Log::debug('usage', collect($res)->toArray());
        $memory = collect($res)
            ->pluck('containers.*.usage.memory')
            ->flatten()
            ->map(fn ($value) => Str::contains($value, 'Mi') ? rtrim($value, 'Mi') * 1024 : rtrim($value, 'Ki'))
            ->sum() / 1024; // Mi
        $cpu = collect($res)
            ->pluck('containers.*.usage.cpu')
            ->flatten()
            ->map(fn ($value) => rtrim($value, 'n'))
            ->sum() / (1000 * 1000 * 1000);

        return [
            'memory' => round($memory, 2) . ' mi',
            'cpu'    => round($cpu, 2) . ' cpu',
        ];
    }
}
