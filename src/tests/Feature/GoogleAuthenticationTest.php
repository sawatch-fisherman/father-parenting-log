<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use RuntimeException;
use Tests\TestCase;

/**
 * Google SSO ログイン（M1）を検証する。
 *
 * @see docs/implementation-plan.md「M1 認証（S1）」
 */
class GoogleAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * `GET /auth/google/redirect` が Google の認可画面へリダイレクトすることを検証する。
     */
    public function test_redirect_route_sends_the_user_to_google(): void
    {
        // Arrange
        Socialite::fake('google');

        // Act
        $response = $this->get('/auth/google/redirect');

        // Assert
        $response->assertRedirect();
    }

    /**
     * 認可画面へ渡す要求スコープが `openid` だけであることを検証する。
     *
     * Socialite の Google ドライバの既定は `openid profile email` で、そのままだと氏名・
     * メールアドレスの提供を同意画面で求めてしまう。本サービスが保存するのは識別子だけなので、
     * 「使わない個人情報は最初から持たない」方針（docs/privacy.md）と食い違う。
     * `Socialite::fake()` は固定URLを返して実際のスコープを隠すため、ここでは fake せず
     * 実際に組み立てられる認可URLを検証する。
     */
    public function test_redirect_requests_only_the_openid_scope(): void
    {
        // Arrange
        config()->set('services.google', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/auth/google/callback',
        ]);

        // Act
        $response = $this->get('/auth/google/redirect');

        // Assert
        $response->assertRedirectContains('accounts.google.com');

        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('scope', $query);
        $this->assertSame('openid', $query['scope']);
    }

    /**
     * 初回ログインで `provider`/`provider_id` を持つ新規ユーザーが作成され、ログイン状態になることを検証する。
     */
    public function test_creates_a_new_user_on_first_login(): void
    {
        // Arrange
        Socialite::fake('google', SocialiteUser::fake(['id' => 'google-user-1']));

        // Act
        $this->get('/auth/google/callback');

        // Assert
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'provider' => 'google',
            'provider_id' => 'google-user-1',
        ]);
        $this->assertSame(1, User::query()->count());
    }

    /**
     * 既存ユーザーが同じ `provider_id` で再ログインしても、ユーザーが重複作成されないことを検証する。
     */
    public function test_logs_in_an_existing_user_without_creating_a_duplicate(): void
    {
        // Arrange
        $user = User::factory()->create(['provider' => 'google', 'provider_id' => 'google-user-2']);
        Socialite::fake('google', SocialiteUser::fake(['id' => 'google-user-2']));

        // Act
        $this->get('/auth/google/callback');

        // Assert
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
    }

    /**
     * 退会済みユーザーが同じGoogleアカウントで再ログインしても、退会前のアカウントには戻らず
     * 別の新規ユーザーとして作成されることを検証する。
     *
     * 退会は行削除ではなく `provider = 'withdrawn'`／`provider_id = null` への書き換えで行うため
     * （docs/decisions.md §1.1）、コールバックの検索条件が退会済み行に一致しないことが
     * 「退会後は過去のデータに戻れない」という保証の前提になっている。
     */
    public function test_a_withdrawn_user_cannot_return_to_their_previous_account(): void
    {
        // Arrange
        $withdrawnUser = User::factory()->create(['provider' => 'google', 'provider_id' => 'google-user-5']);
        Profile::factory()->create(['user_id' => $withdrawnUser->id]);

        $withdrawnUser->forceFill([
            'provider' => 'withdrawn',
            'provider_id' => null,
            'remember_token' => null,
            'withdrawn_at' => now(),
        ])->save();

        Socialite::fake('google', SocialiteUser::fake(['id' => 'google-user-5']));

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $this->assertAuthenticated();
        $this->assertNotSame($withdrawnUser->id, Auth::id());
        $this->assertSame(2, User::query()->count());

        // Assert: 退会前のプロフィールを引き継がないため、プロフィール登録からやり直しになる
        $response->assertRedirect(route('profile.register'));
    }

    /**
     * プロフィール登録済みユーザーはログイン後 `home` へリダイレクトされることを検証する。
     */
    public function test_redirects_a_user_with_a_profile_to_home(): void
    {
        // Arrange
        $user = User::factory()->create(['provider' => 'google', 'provider_id' => 'google-user-3']);
        Profile::factory()->create(['user_id' => $user->id]);
        Socialite::fake('google', SocialiteUser::fake(['id' => 'google-user-3']));

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect(route('home'));
    }

    /**
     * プロフィール未登録ユーザーはログイン後 `profile.register` へリダイレクトされることを検証する。
     */
    public function test_redirects_a_user_without_a_profile_to_profile_registration(): void
    {
        // Arrange
        Socialite::fake('google', SocialiteUser::fake(['id' => 'google-user-4']));

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect(route('profile.register'));
    }

    /**
     * Google 認可中の失敗（拒否・state不一致など）を `login` へエラーメッセージ付きで戻すことを検証する。
     *
     * Socialite の例外を握りつぶさないと、ユーザーが認可を拒否しただけで未処理の例外による
     * 500エラー画面が出てしまう。
     */
    public function test_redirects_to_login_with_an_error_when_socialite_fails(): void
    {
        // Arrange
        Socialite::fake('google', function () {
            throw new RuntimeException('denied');
        });

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $response->assertSessionHas('error');
    }

    /**
     * Google 認可の失敗後、`login` へのリダイレクト先で `flash.error` がInertia propsに届くことを検証する。
     *
     * `assertSessionHas('error')` はセッションにキーが積まれたことしか保証せず、
     * `HandleInertiaRequests::share()` のキー名変更やLogin.vue側の参照漏れを検知できない。
     * セッション→共有props→画面表示の経路をここで固定する。
     */
    public function test_login_failure_error_reaches_inertia_props(): void
    {
        // Arrange
        Socialite::fake('google', function () {
            throw new RuntimeException('denied');
        });

        // Act
        $this->get('/auth/google/callback');

        // Assert
        $this->get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('flash.error', __('auth.google_login_failed')),
        );
    }

    /**
     * 認証失敗を経ていない通常のログイン画面表示では `flash.error` が `null` であることを検証する。
     *
     * `flash.error` の型が `string | null` であることをLogin.vue側と一致させておくための対称テスト。
     */
    public function test_login_page_has_no_flash_error_by_default(): void
    {
        // Act & Assert
        $this->get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('flash.error', null),
        );
    }

    /**
     * `provider_id` が空のコールバックでログインもユーザー作成もしないことを検証する。
     *
     * `provider_id` は退会者のために NULL 許容にしてあるため、null のままでもユーザーを
     * 作成できてしまう。一度 `provider = 'google'` かつ `provider_id IS NULL` の行ができると、
     * 次に同じく空のコールバックが来たとき `whereNull` でその行に一致し、別人としてログイン
     * させてしまう。Googleは必ず `sub` を返すが多層防御として弾く。
     */
    public function test_rejects_a_callback_without_a_provider_id(): void
    {
        // Arrange
        Socialite::fake('google', SocialiteUser::fake(['id' => null]));

        // Act
        $response = $this->get('/auth/google/callback');

        // Assert
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    /**
     * 認証済みユーザーが `login` にアクセスすると `home` へリダイレクトされることを検証する（guest ミドルウェア）。
     */
    public function test_redirects_authenticated_user_away_from_login(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/login');

        // Assert
        $response->assertRedirect(route('home'));
    }

    /**
     * ログアウトでセッションが破棄され、以後は保護ルートに未認証としてリダイレクトされることを検証する。
     */
    public function test_logout_invalidates_the_session(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/logout');

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertGuest();

        // Act: ログアウト後に保護ルートへアクセスする
        $response = $this->get('/');

        // Assert
        $response->assertRedirect(route('login'));
    }
}
