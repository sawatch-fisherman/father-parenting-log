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
 * 以下の3ルートには適用しない（理由はそれぞれ異なる）：
 * `profile.register`・`profile.store` は適用すると無限リダイレクトになるため、
 * `logout` は適用するとプロフィール未登録ユーザーがログアウトできなくなる
 * （無限ループではなくロックアウト）ため。`locale.update`（`routes/web.php`）は
 * そもそも `auth` グループの外にあり、この一覧は対象外であることの確認のために挙げている。
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
