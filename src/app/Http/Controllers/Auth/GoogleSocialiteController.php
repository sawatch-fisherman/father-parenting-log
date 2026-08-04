<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GoogleSocialiteController extends Controller
{
    private const string PROVIDER = 'google';

    /**
     * Google の認可画面へリダイレクトする。
     */
    public function redirect(): Response
    {
        return Socialite::driver(self::PROVIDER)->redirect();
    }

    /**
     * Google からのコールバックを受け取り、ログイン（または新規作成）する。
     *
     * `provider` + `provider_id` の組で find/create し、氏名・メールアドレスは保存しない
     * （docs/privacy.md「本サービスが保存しているのはログイン時に受け取る識別子だけ」）。
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver(self::PROVIDER)->user();
        } catch (Throwable) {
            return redirect()->route('login')->with('error', __('auth.google_login_failed'));
        }

        $user = User::query()->firstOrCreate([
            'provider' => self::PROVIDER,
            'provider_id' => $socialiteUser->getId(),
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return $user->profile()->exists()
            ? redirect()->route('home')
            : redirect()->route('profile.register');
    }
}
