<?php

namespace App\Http\Controllers;

use App\Models\Ns;
use App\Models\Project;
use App\Services\K8sApi;
use Illuminate\Http\Request;
use App\Events\NamespaceCreated;
use App\Events\NamespaceDeleting;

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
                $links = collect(
                    $ns
                        ->projects
                        ->map(
                            fn (Project $p) => ['name' => $p->name, 'links' => $p->getExternalIps()]
                        )
                )
                    ->values()
                    ->toArray();

                return [
                    'owned'     => auth()->id() == $ns->user_id,
                    'id'        => $ns->id,
                    'namespace' => $ns->name,
                    'projects'  => $ns->projects->map->only('id', 'env', 'name', 'env_file_type', 'all_pod_ready', 'project_id', 'commit', 'branch')->toArray(),
                    'links'     => $links,
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

        $memory = collect($res)
            ->pluck('containers.*.usage.memory')
            ->flatten()
            ->map(fn ($value) => $this->getMemoryUsage($value))
            ->sum() / pow(2, 20);
        $cpu = collect($res)
            ->pluck('containers.*.usage.cpu')
            ->flatten()
            ->map(fn ($value) => $this->getCpuUsage($value))
            ->sum();

        return [
            'memory' => round($memory, 3) . ' mi',
            'cpu'    => round($cpu, 3) . ' cpu',
        ];
    }

    public function getCpuUsage($value)
    {
        $last = substr($value, -1);

        switch ($last) {
            case 'n':
                return rtrim($value, $last) * pow(10, -9);
            case 'u':
                return rtrim($value, $last) * pow(10, -6);
            case 'm':
                return rtrim($value, $last) * pow(10, -3);
            case 'k':
                return rtrim($value, $last) * pow(10, 3);
            case 'M':
                return rtrim($value, $last) * pow(10, 6);
            case 'G':
                return rtrim($value, $last) * pow(10, 9);
            default:
                throw new \Exception('unknown usage: ' . $value);
        }
    }

    private function getMemoryUsage($value)
    {
        $last = substr($value, -2);
        switch ($last) {
            case 'Ki':
                return rtrim($value, $last) * pow(2, 10);
            case 'Mi':
                return rtrim($value, $last) * pow(2, 20);
            case 'Gi':
                return rtrim($value, $last) * pow(2, 30);
            case 'Ti':
                return rtrim($value, $last) * pow(2, 40);
            case 'Pi':
                return rtrim($value, $last) * pow(2, 50);
            case 'Ei':
                return rtrim($value, $last) * pow(2, 60);
            default:
                throw new \Exception('unknown usage: ' . $value);
        }
    }
}
