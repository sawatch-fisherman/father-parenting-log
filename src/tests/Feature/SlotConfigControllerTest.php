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
 * S9（ピン留め設定画面）を検証する（M8）。
 *
 * @see docs/implementation-plan.md「M8 設定（S7, S9）」
 */
class SlotConfigControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * S9が現在のピン留め（8枠。空きはnull）とアクセス可能な育児行動一覧をInertia propsとして
     * 受け取ることを検証する。
     */
    public function test_edit_page_receives_current_slots_and_available_care_actions(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $pinned = CareAction::factory()->create(['name' => 'おむつ交換', 'sort_order' => 1]);
        CareAction::factory()->create(['name' => 'お風呂', 'sort_order' => 2]);

        UserSlotConfig::factory()->create([
            'user_id' => $user->id,
            'slot_position' => 1,
            'care_action_id' => $pinned->id,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/settings/slots');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/Slots')
            ->has('slots', 8)
            ->where('slots.0.careActionId', $pinned->id)
            ->where('slots.0.name', 'おむつ交換')
            ->where('slots.1', null)
            ->has('careActions', 2),
        );
    }

    /**
     * `PUT /settings/slots` で8枠すべてが送信内容へ置き換わることを検証する。
     */
    public function test_update_replaces_all_slots(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $oldCareAction = CareAction::factory()->create();
        UserSlotConfig::factory()->create([
            'user_id' => $user->id,
            'slot_position' => 1,
            'care_action_id' => $oldCareAction->id,
        ]);

        $newCareActions = CareAction::factory()->count(8)->create();

        // Act
        $response = $this->actingAs($user)->put('/settings/slots', [
            'slots' => $newCareActions->map(fn (CareAction $careAction, int $index): array => [
                'slot_position' => $index + 1,
                'care_action_id' => $careAction->id,
            ])->all(),
        ]);

        // Assert
        $response->assertRedirect(route('settings.index'));
        $response->assertInertiaFlash('success', 'ピン留めを更新しました');
        $this->assertDatabaseMissing('user_slot_configs', ['care_action_id' => $oldCareAction->id]);
        $this->assertSame(
            $newCareActions->pluck('id')->all(),
            UserSlotConfig::query()->where('user_id', $user->id)->orderBy('slot_position')->pluck('care_action_id')->all(),
        );
    }

    /**
     * 8個未満（空配列を含む）での保存が通ることを検証する（`user_slot_configs` の行数の
     * 不変条件は「最大8個」であり「常に8個」ではないため。docs/data-model.md ⑤）。
     */
    public function test_update_allows_fewer_than_eight_slots(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        // Act
        $response = $this->actingAs($user)->put('/settings/slots', [
            'slots' => [
                ['slot_position' => 1, 'care_action_id' => $careAction->id],
            ],
        ]);

        // Assert
        $response->assertRedirect(route('settings.index'));
        $this->assertSame(1, UserSlotConfig::query()->where('user_id', $user->id)->count());
    }

    /**
     * 空配列での保存も拒否されず、全スロットが空きになることを検証する。
     */
    public function test_update_allows_an_empty_slot_list(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        UserSlotConfig::factory()->create(['user_id' => $user->id, 'slot_position' => 1]);

        // Act
        $response = $this->actingAs($user)->put('/settings/slots', ['slots' => []]);

        // Assert
        $response->assertRedirect(route('settings.index'));
        $this->assertSame(0, UserSlotConfig::query()->where('user_id', $user->id)->count());
    }

    /**
     * 9個以上のスロットは`max:8`で拒否され、既存のピン留めが変更されないことを検証する。
     */
    public function test_update_rejects_more_than_eight_slots(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $existing = UserSlotConfig::factory()->create(['user_id' => $user->id, 'slot_position' => 1]);

        $careActions = CareAction::factory()->count(9)->create();

        // Act
        $response = $this->actingAs($user)->put('/settings/slots', [
            'slots' => $careActions->map(fn (CareAction $careAction, int $index): array => [
                'slot_position' => $index + 1,
                'care_action_id' => $careAction->id,
            ])->all(),
        ]);

        // Assert
        $response->assertSessionHasErrors('slots');
        $this->assertDatabaseHas('user_slot_configs', ['id' => $existing->id]);
    }

    /**
     * 同じ育児行動を複数スロットへ重複して割り当てると`distinct`で拒否されることを検証する
     * （`UNIQUE(user_id, care_action_id)`のアプリ層側の担保）。
     */
    public function test_update_rejects_a_duplicate_care_action_across_slots(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        // Act
        $response = $this->actingAs($user)->put('/settings/slots', [
            'slots' => [
                ['slot_position' => 1, 'care_action_id' => $careAction->id],
                ['slot_position' => 2, 'care_action_id' => $careAction->id],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('slots.0.care_action_id');
        $this->assertSame(0, UserSlotConfig::query()->where('user_id', $user->id)->count());
    }

    /**
     * 同じ`slot_position`を複数回送ると`distinct`で拒否されることを検証する
     * （`UNIQUE(user_id, slot_position)`のアプリ層側の担保）。
     */
    public function test_update_rejects_a_duplicate_slot_position(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        [$careActionA, $careActionB] = CareAction::factory()->count(2)->create();

        // Act
        $response = $this->actingAs($user)->put('/settings/slots', [
            'slots' => [
                ['slot_position' => 1, 'care_action_id' => $careActionA->id],
                ['slot_position' => 1, 'care_action_id' => $careActionB->id],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('slots.0.slot_position');
    }

    /**
     * `slot_position`が1〜8の範囲外だと拒否されることを検証する。
     */
    public function test_update_rejects_a_slot_position_out_of_range(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        // Act
        $response = $this->actingAs($user)->put('/settings/slots', [
            'slots' => [
                ['slot_position' => 9, 'care_action_id' => $careAction->id],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('slots.0.slot_position');
    }

    /**
     * 他ユーザーのカスタム育児行動は許可されず拒否されることを検証する
     * （`CareAction::scopeAccessibleTo`）。
     */
    public function test_update_rejects_a_care_action_not_accessible_to_the_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $otherUser = User::factory()->create();
        $othersCareAction = CareAction::factory()->create(['user_id' => $otherUser->id]);

        // Act
        $response = $this->actingAs($user)->put('/settings/slots', [
            'slots' => [
                ['slot_position' => 1, 'care_action_id' => $othersCareAction->id],
            ],
        ]);

        // Assert
        $response->assertSessionHasErrors('slots.0.care_action_id');
        $this->assertSame(0, UserSlotConfig::query()->where('user_id', $user->id)->count());
    }

    /**
     * 2つのピン留みを入れ替える保存が、DBのUNIQUE制約違反にならず成功することを検証する。
     *
     * 1行ずつ`UPDATE`する実装だと、片方の行を書き換えた時点でもう片方と`care_action_id`が
     * 一時的に重複し`UNIQUE(user_id, care_action_id)`に触れてしまう。`update`が
     * delete-insert方式（全削除→全挿入）であることの回帰テスト（docs/decisions.md §1.3）。
     */
    public function test_swapping_two_pinned_slots_does_not_violate_the_unique_constraint(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careActionA = CareAction::factory()->create();
        $careActionB = CareAction::factory()->create();

        UserSlotConfig::factory()->create([
            'user_id' => $user->id,
            'slot_position' => 1,
            'care_action_id' => $careActionA->id,
        ]);
        UserSlotConfig::factory()->create([
            'user_id' => $user->id,
            'slot_position' => 2,
            'care_action_id' => $careActionB->id,
        ]);

        // Act
        $response = $this->actingAs($user)->put('/settings/slots', [
            'slots' => [
                ['slot_position' => 1, 'care_action_id' => $careActionB->id],
                ['slot_position' => 2, 'care_action_id' => $careActionA->id],
            ],
        ]);

        // Assert
        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('user_slot_configs', [
            'user_id' => $user->id,
            'slot_position' => 1,
            'care_action_id' => $careActionB->id,
        ]);
        $this->assertDatabaseHas('user_slot_configs', [
            'user_id' => $user->id,
            'slot_position' => 2,
            'care_action_id' => $careActionA->id,
        ]);
    }

    /**
     * 未認証で`GET /settings/slots`にアクセスすると`login`へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_to_edit_redirects_to_login(): void
    {
        // Act
        $response = $this->get('/settings/slots');

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * 未認証で`PUT /settings/slots`にアクセスすると`login`へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_to_update_redirects_to_login(): void
    {
        // Act
        $response = $this->put('/settings/slots', ['slots' => []]);

        // Assert
        $response->assertRedirect(route('login'));
    }
}
