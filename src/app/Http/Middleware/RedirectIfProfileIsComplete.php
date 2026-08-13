<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 登録済みユーザーを `profile.register`（S2）から `home` へ追い出す。
 *
 * `EnsureProfileIsComplete` の逆方向。S2は「初回ログイン時のみ」（docs/screens.md:111）だが、
 * 登録完了後にブラウザバックで戻る・複数タブで開いたままにするといった通常操作で
 * `profile.register`・`profile.store` に到達しうる。`profiles.user_id` はUNIQUE制約のため、
 * このガードが無いと `POST /profile` の再送が例外（500）になってしまう
 * （`profiles` の `user_id` UNIQUE制約。docs/data-model.md ②）。
 */
class RedirectIfProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->profile()->exists()) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
