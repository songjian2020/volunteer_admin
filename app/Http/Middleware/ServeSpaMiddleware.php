<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 后台是 React History 路由。菜单跳转只改浏览器地址，不请求服务器；
 * 刷新时浏览器会向 Laravel 要 /volunteer/volunteer/pending 这类前端路径，
 * 后端没有对应接口就会返回 Route Not Exist。浏览器打开页面时直接回 index.html。
 */
class ServeSpaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldServeSpa($request)) {
            return $this->spaResponse();
        }

        return $next($request);
    }

    protected function shouldServeSpa(Request $request): bool
    {
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        // Axios 接口（Accept: application/json 或 /index.php 前缀）继续走后端
        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }

        $path = ltrim($request->path(), '/');
        if ($path === 'index.php' || str_starts_with($path, 'index.php/')) {
            return false;
        }

        if (preg_match('/\.(js|css|map|json|png|jpe?g|gif|webp|ico|svg|woff2?|ttf|eot|txt|xml|mp4|mp3)$/i', $path)) {
            return false;
        }

        $accept = (string) $request->header('Accept', '');
        if ($accept === '' || !str_contains($accept, 'text/html')) {
            return false;
        }

        return is_file(public_path('index.html'));
    }

    protected function spaResponse(): Response
    {
        return response(
            file_get_contents(public_path('index.html')),
            200,
            [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'no-cache',
            ]
        );
    }
}
