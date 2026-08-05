<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GoogleSocialiteController extends Controller
{
    private const string PROVIDER = 'google';

    /**
     * Google の認可画面へリダイレクトする。
     *
     * 要求スコープを `openid` だけに絞る。Socialite の既定は `openid profile email` だが、
     * 本サービスが使うのは識別子（ID トークンの `sub`）のみで氏名・メールアドレスは保存しない。
     * 既定のままだと同意画面で「名前、メールアドレス」の提供を求めることになり、
     * 「使わない個人情報は最初から持たない」方針と食い違う（docs/privacy.md）。
     *
     * `setScopes()` は `Socialite::driver()` の戻り値型である `Provider` 契約ではなく
     * `AbstractProvider` 側に生えているため、型を明示してから呼ぶ。
     */
    public function redirect(): Response
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver(self::PROVIDER);

        return $driver->setScopes(['openid'])->redirect();
    }

    /**
     * Google からのコールバックを受け取り、ログイン（または新規作成）する。
     *
     * `provider` + `provider_id` の組で find/create し、氏名・メールアドレスは保存しない
     * （docs/privacy.md「本サービスが保存しているのはログイン時に受け取る識別子だけ」）。
     *
     * 失敗は理由を問わず同じ画面へ戻すが、認可拒否・設定不備・state 不一致を後から
     * 切り分けられるよう、例外の種別とメッセージだけはログに残す。アクセストークンと
     * `sub` は出さない（ログ自体が個人情報の保管先にならないようにするため）。
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver(self::PROVIDER)->user();
        } catch (Throwable $e) {
            Log::warning('Google authentication callback failed.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->redirectToLoginWithError();
        }

        // `provider_id` は退会者向けに NULL 許容のため、null のまま検索すると `whereNull` に
        // 落ちて既存の退会済み行に一致してしまう。Google は必ず `sub` を返すが、
        // 取り違えたままログインさせないよう多層防御として弾く。
        $providerId = $socialiteUser->getId();

        if (blank($providerId)) {
            Log::warning('Google authentication callback returned an empty provider id.');

            return $this->redirectToLoginWithError();
        }

        $user = User::query()->firstOrCreate([
            'provider' => self::PROVIDER,
            'provider_id' => $providerId,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return $user->profile()->exists()
            ? redirect()->route('home')
            : redirect()->route('profile.register');
    }

    /**
     * ログイン失敗としてログイン画面へ戻す。
     *
     * 失敗の詳細は画面に出さない（どのアカウントが存在するかを推測させないため）。
     */
    private function redirectToLoginWithError(): RedirectResponse
    {
        return redirect()->route('login')->with('error', __('auth.google_login_failed'));
    }
}
