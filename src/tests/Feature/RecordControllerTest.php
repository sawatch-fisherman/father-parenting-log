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
 * S3（記録画面）の骨格（M3）を検証する。タップ操作（短タップ即記録・長押しでS10）の配線はM4で行うため対象外。
 *
 * @see docs/implementation-plan.md「M3 記録の骨格＋グローバルナビ（S3, 共通ナビ）」
 */
class RecordControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * ピン留め済みの育児行動が `slot_position` 順に並んで渡されることを検証する。
     *
     * わざと `slot_position` と逆順でレコードを作成し、応答側が保存順ではなく
     * `slot_position` 順で並べ直していることを確認する。
     */
    public function test_index_returns_pinned_slots_ordered_by_slot_position(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $second = CareAction::factory()->create(['name' => '2番目']);
        $first = CareAction::factory()->create(['name' => '1番目']);

        UserSlotConfig::factory()->create([
            'user_id' => $user->id,
            'slot_position' => 2,
            'care_action_id' => $second->id,
        ]);
        UserSlotConfig::factory()->create([
            'user_id' => $user->id,
            'slot_position' => 1,
            'care_action_id' => $first->id,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Record/Index')
            ->where('slots.0.name', '1番目')
            ->where('slots.0.careActionId', $first->id)
            ->where('slots.1.name', '2番目')
            ->where('slots.1.careActionId', $second->id),
        );
    }

    /**
     * ピン留めが8個未満でも、行の無い `slot_position` が `null`（空きスロット）として
     * 埋められ、常に8要素の配列で返ることを検証する。
     */
    public function test_index_pads_missing_slot_positions_with_null(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $careAction = CareAction::factory()->create(['name' => 'おむつ交換']);

        UserSlotConfig::factory()->create([
            'user_id' => $user->id,
            'slot_position' => 3,
            'care_action_id' => $careAction->id,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Record/Index')
            ->has('slots', 8)
            ->where('slots.0', null)
            ->where('slots.1', null)
            ->where('slots.2.name', 'おむつ交換')
            ->where('slots.3', null),
        );
    }

    /**
     * 他ユーザーが同じ `slot_position` にピン留めしていても、自分のピン留めだけが返り、
     * 他ユーザーの育児行動名が混入しないことを検証する。
     *
     * `user_slot_configs` は `UNIQUE(user_id, slot_position)` のみで `slot_position` 単体は
     * ユーザー間で重複しうるため、スコープを落とすと他人の記録名が画面に漏れる構造になっている。
     */
    public function test_index_does_not_leak_another_users_pinned_slots(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $ownCareAction = CareAction::factory()->create(['name' => '自分のピン留め']);
        UserSlotConfig::factory()->create([
            'user_id' => $user->id,
            'slot_position' => 8,
            'care_action_id' => $ownCareAction->id,
        ]);

        $otherUser = User::factory()->create();
        Profile::factory()->create(['user_id' => $otherUser->id]);
        $otherCareAction = CareAction::factory()->create(['name' => '他人のピン留め']);
        UserSlotConfig::factory()->create([
            'user_id' => $otherUser->id,
            'slot_position' => 8,
            'care_action_id' => $otherCareAction->id,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Record/Index')
            ->where('slots.7.name', '自分のピン留め')
            ->where('slots.7.careActionId', $ownCareAction->id),
        );
    }

    /**
     * ピン留めが1件も無いユーザーでも、8個すべて空きスロットの配列でエラーなく描画できることを検証する。
     */
    public function test_index_renders_all_empty_slots_when_no_pins_exist(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Record/Index')
            ->has('slots', 8)
            ->where('slots.0', null)
            ->where('slots.7', null),
        );
    }

    /**
     * 未認証で `GET /` にアクセスすると `login` へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_redirects_to_login(): void
    {
        // Act
        $response = $this->get('/');

        // Assert
        $response->assertRedirect(route('login'));
    }
}
