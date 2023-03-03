<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DemoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next) {
//        echo $request->getRequestUri();
        $exclude_uri = array(
            '/login',
            '/api/student/login',
            '/api/parent/login',
            '/api/teacher/login'
        );
//        dd($request->getRequestUri());
        if (env('DEMO_MODE')) {
            if (!$request->isMethod('get') && !in_array($request->getRequestUri(), $exclude_uri)) {
                return response()->json(array(
                    'error' => true,
                    'message' => "This is not allowed in the Demo Version.",
                    'code' => 112
                ));
            }
        }
        return $next($request);
    }
}
