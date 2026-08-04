<?php

namespace Tests\Feature;

use App\Models\CareAction;
use App\Models\CareLog;
use App\Models\Profile;
use App\Models\Title;
use App\Models\User;
use App\Support\CareActionId;
use App\Support\TitleId;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * TotoOps標準17行の`sort_order`が`1`〜`17`の重複なし連番であることを検証する。
     *
     * カテゴリ順に並べ替えた結果`id`の昇順とは一致しなくなるが、採番自体は詰まっている必要がある
     * （ユーザーカスタム行は`18`から続けて採番するため。docs/data-model.md ③）。
     *
     * Seeder投入済みの状態そのものが検証対象のため、実行（Act）にあたる操作を持たない。
     */
    public function test_standard_care_actions_are_numbered_as_a_gapless_sort_order(): void
    {
        // Arrange
        $this->seed();

        $sortOrders = CareAction::query()
            ->whereNull('user_id')
            ->orderBy('sort_order')
            ->pluck('sort_order')
            ->all();

        // Assert
        $this->assertSame(range(1, 17), $sortOrders);
    }

    public function test_standard_master_rows_keep_their_fixed_ids(): void
    {
        // Arrange
        $this->seed();

        // Assert: 固定IDで引けること自体（findOrFail）も検証の一部
        $diaperChange = CareAction::query()->findOrFail(CareActionId::DIAPER_CHANGE);

        $this->assertSame('おむつ交換', $diaperChange->name);

        $title = Title::query()->findOrFail(TitleId::DIAPER_CHANGE_COUNT_TIER1);

        $this->assertSame(CareActionId::DIAPER_CHANGE, $title->care_action_id);
    }

    public function test_standard_care_actions_stay_inside_the_reserved_id_range(): void
    {
        // Arrange
        $this->seed();

        // Assert
        $this->assertSame(0, CareAction::query()
            ->whereNull('user_id')
            ->where('id', '>=', CareActionId::CUSTOM_ID_FLOOR)
            ->count(), '標準行が予約域の外に採番されている');
    }

    /**
     * ユーザーカスタム行が予約域を侵食しないことを検証する。
     *
     * この分離が崩れると、運用開始後にTotoOps標準の育児行動を追加しようとした時点で
     * 標準側の連番が枯渇する（docs/decisions.md §1.3「ID／主キーの形式」例外規定）。
     */
    public function test_custom_care_actions_are_numbered_above_the_reserved_id_range(): void
    {
        // Arrange
        $this->seed();

        // Act
        $customCareAction = CareAction::factory()->create(['user_id' => User::factory()]);

        // Assert
        $this->assertGreaterThanOrEqual(CareActionId::CUSTOM_ID_FLOOR, $customCareAction->id);
    }

    /**
     * URL・APIに露出するIDだけがULIDであることを検証する。
     *
     * @see docs/decisions.md §1.3「主キー形式の判断基準」
     */
    public function test_only_exposed_ids_use_ulids(): void
    {
        // Act
        $careLog = CareLog::factory()->create();

        // Assert
        $this->assertTrue(Str::isUlid($careLog->id));
        $this->assertTrue(Str::isUlid($careLog->user_id));

        $this->assertIsInt(Profile::factory()->create()->id);
        $this->assertIsInt(CareAction::factory()->create()->id);
    }

    public function test_seeding_twice_does_not_duplicate_master_rows(): void
    {
        // Arrange
        $this->seed();

        $careActionCount = CareAction::query()->count();
        $titleCount = Title::query()->count();

        // Act
        $this->seed();

        // Assert
        $this->assertSame($careActionCount, CareAction::query()->count());
        $this->assertSame($titleCount, Title::query()->count());
    }

    public function test_all_migrations_roll_back_in_reverse_dependency_order(): void
    {
        // Act
        $this->artisan('migrate:rollback')->assertSuccessful();

        // Assert
        $this->assertFalse(Schema::hasTable('care_logs'));
        $this->assertFalse(Schema::hasTable('users'));

        // 後始末: 後続テストが引き継ぐスキーマを元に戻す
        $this->artisan('migrate')->assertSuccessful();
    }
}
