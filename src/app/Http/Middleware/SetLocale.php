<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * 受信したリクエストを処理する。
     *
     * サポート対象外・未設定の cookie は無視し、`config('app.locale')` のままにする。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        if (is_string($locale) && in_array($locale, Config::array('totoops.supported_locales'), true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
