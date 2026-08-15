<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * グローバルナビの4遷移先（記録/履歴/集計/設定）が、実装済みのものは本実装、
 * 未実装のものはM3時点のプレースホルダとして到達可能であることを検証する。
 *
 * @see docs/implementation-plan.md「M3 記録の骨格＋グローバルナビ（S3, 共通ナビ）」
 */
class GlobalNavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * ナビの4遷移先（URIと対応するInertiaコンポーネント名の組）を返す。
     *
     * @return iterable<string, array{string, string}>
     */
    public static function navDestinations(): iterable
    {
        yield 'record' => ['/', 'Record/Index'];
        yield 'history' => ['/history', 'History/Index'];
        yield 'stats' => ['/stats', 'Stats/Index'];
        yield 'settings' => ['/settings', 'Settings/Index'];
    }

    /**
     * ナビの4遷移先のURIだけを返す（リダイレクト系のテストはコンポーネント名を検証しないため）。
     *
     * @return iterable<string, array{string}>
     */
    public static function navUris(): iterable
    {
        foreach (self::navDestinations() as $key => $destination) {
            yield $key => [$destination[0]];
        }
    }

    /**
     * プロフィール登録済みユーザーが、ナビの4遷移先すべてに200でアクセスでき、
     * それぞれ対応するInertiaコンポーネントが返ることを検証する。
     */
    #[DataProvider('navDestinations')]
    public function test_nav_destination_is_reachable_for_a_user_with_a_complete_profile(string $uri, string $component): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get($uri);

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component($component));
    }

    /**
     * 未認証で各ナビ遷移先にアクセスすると `login` へリダイレクトされることを検証する。
     */
    #[DataProvider('navUris')]
    public function test_nav_destination_redirects_unauthenticated_users_to_login(string $uri): void
    {
        // Act
        $response = $this->get($uri);

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * プロフィール未登録ユーザーが各ナビ遷移先へ直接アクセスすると
     * `profile.register` へリダイレクトされることを検証する（`EnsureProfileIsComplete`）。
     */
    #[DataProvider('navUris')]
    public function test_nav_destination_redirects_users_without_a_profile_to_registration(string $uri): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get($uri);

        // Assert
        $response->assertRedirect(route('profile.register'));
    }
}
