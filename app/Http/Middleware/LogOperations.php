<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use App\Models\OperationLog;
use Illuminate\Http\Request;

class LogOperations
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response)
    {
        if ($request->routeIs('ping')) {
            return;
        }

        $data = [
            'user'          => optional(auth()->user())->user_name,
            'ip'            => $request->ip(),
            'time'          => microtime(true) - LARAVEL_START,
            'path'          => $request->fullUrl(),
            'request'       => $request->all(),
            'response'      => ($request->expectsJson() || $response->isServerError()) ? Str::limit($response->content(), 500) : null,
            'response_code' => $response->status(),
            'method'        => $request->method(),
            'user_agent'    => $request->userAgent(),
        ];

        OperationLog::query()->create($data);

//        dispatch(function () use ($data) {
//            OperationLog::query()->create($data);
//        });
    }
}
