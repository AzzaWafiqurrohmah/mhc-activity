<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleMiddleware
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! Modules::can($request->user(), $module)) {
            abort(403, 'Divisi kamu tidak punya akses ke menu ini.');
        }

        return $next($request);
    }
}