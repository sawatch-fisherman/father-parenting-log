<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * プロフィール未登録のユーザーを `profile.register`（S2）へ誘導する。
 *
 * ログイン直後のcallback分岐（M1）だけでは、プロフィール未登録のまま `/` 等へ
 * 直接アクセスするケースを防げないため、`auth` 系ルートに重ねて適用する。
 * `profile.register`・`profile.store`・`logout`・`locale.update` には適用しない
 * （適用すると無限リダイレクトになる）。
 *
 * @see docs/implementation-plan.md「M2 プロフィール（S2, S8）」
 */
class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->profile()->exists()) {
            return redirect()->route('profile.register');
        }

        return $next($request);
    }
}
