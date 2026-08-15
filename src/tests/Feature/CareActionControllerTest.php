<?php

namespace Tests\Feature;

use App\Models\CareAction;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserSlotConfig;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * S4（「その他」育児行動選択画面）を検証する（M4）。
 *
 * @see docs/implementation-plan.md「M4 育児ログ登録（S3 短タップ, S4, S10）」
 */
class CareActionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * ピン留め済みの育児行動が一覧から除外されることを検証する。
     */
    public function test_other_excludes_pinned_care_actions(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $pinned = CareAction::factory()->create(['name' => 'ピン留め済み', 'sort_order' => 1]);
        $notPinned = CareAction::factory()->create(['name' => '未ピン留め', 'sort_order' => 2]);

        UserSlotConfig::factory()->create([
            'user_id' => $user->id,
            'slot_position' => 1,
            'care_action_id' => $pinned->id,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/care-actions/other');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('CareActions/Other')
            ->has('careActions', 1)
            ->where('careActions.0.id', $notPinned->id)
            ->where('careActions.0.name', '未ピン留め'),
        );
    }

    /**
     * 他ユーザーのカスタム育児行動は一覧に含まれないことを検証する（`CareAction::scopeAccessibleTo`）。
     */
    public function test_other_excludes_another_users_custom_care_action(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $otherUser = User::factory()->create();
        CareAction::factory()->create(['user_id' => $otherUser->id, 'name' => '他人のカスタム']);
        CareAction::factory()->create(['name' => '標準行動']);

        // Act
        $response = $this->actingAs($user)->get('/care-actions/other');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('CareActions/Other')
            ->has('careActions', 1)
            ->where('careActions.0.name', '標準行動'),
        );
    }

    /**
     * ピン留めが1件も無い場合は、アクセス可能な育児行動全件が一覧に含まれることを検証する。
     */
    public function test_other_returns_all_accessible_care_actions_when_nothing_is_pinned(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        CareAction::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($user)->get('/care-actions/other');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('CareActions/Other')
            ->has('careActions', 3),
        );
    }

    /**
     * 未認証で`GET /care-actions/other`にアクセスすると`login`へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_redirects_to_login(): void
    {
        // Act
        $response = $this->get('/care-actions/other');

        // Assert
        $response->assertRedirect(route('login'));
    }
}
