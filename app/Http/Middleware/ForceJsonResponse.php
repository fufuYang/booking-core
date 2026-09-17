<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 強制 API 路由一律以 JSON 回應。
 *
 * 少了這層，忘記帶 Accept: application/json 的客戶端（curl 預設就是）
 * 在驗證失敗時會收到 302 轉址而不是 422，很難除錯。
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
