<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
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
     * 未認証で保護ルートへアクセスすると `login` へリダイレクトされることを検証する。
     */
    public function test_redirects_unauthenticated_access_to_login(): void
    {
        // Act
        $response = $this->get('/');

        // Assert
        $response->assertRedirect(route('login'));
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
