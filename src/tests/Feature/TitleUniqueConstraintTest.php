<?php

namespace Tests\Feature;

use App\Enums\TitleConditionType;
use App\Models\CareAction;
use App\Models\Title;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TitleUniqueConstraintTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * 同一系統（育児行動＋条件種別）に、同じしきい値の称号を2件登録できないことを検証する。
     */
    public function test_duplicate_threshold_within_the_same_series_is_rejected(): void
    {
        // Arrange
        $careAction = CareAction::factory()->create();

        Title::factory()->create([
            'care_action_id' => $careAction->id,
            'condition_type' => TitleConditionType::Count,
            'condition_value' => 10,
        ]);

        // Assert: 例外の期待はPHPUnitの仕様上Actより前に宣言する
        $this->expectException(QueryException::class);

        // Act: 同一系統に同じしきい値をもう1件入れる
        Title::factory()->create([
            'care_action_id' => $careAction->id,
            'condition_type' => TitleConditionType::Count,
            'condition_value' => 10,
        ]);
    }

    /**
     * 条件種別が違えば、しきい値が同じ称号でも共存できることを検証する。
     *
     * 系統は`care_action_id`と`condition_type`の組で決まるため、条件種別が違えば
     * しきい値が同じでも別系統として共存できる（docs/data-model.md ⑥）。
     */
    public function test_same_threshold_is_allowed_for_a_different_condition_type(): void
    {
        // Arrange
        $careAction = CareAction::factory()->create();

        Title::factory()->create([
            'care_action_id' => $careAction->id,
            'condition_type' => TitleConditionType::Count,
            'condition_value' => 10,
        ]);

        // Act: しきい値は同じまま、条件種別だけを変えて入れる
        Title::factory()->create([
            'care_action_id' => $careAction->id,
            'condition_type' => TitleConditionType::Streak,
            'condition_value' => 10,
        ]);

        // Assert
        $this->assertSame(2, Title::query()->where('care_action_id', $careAction->id)->count());
    }

    /**
     * 全体合計称号（`care_action_id IS NULL`）はMySQLがUNIQUE内のNULL同士を別物として扱うため
     * DBのUNIQUE制約では重複を弾けない。Seeder固定マスタ側で重複が起きていないことをここで担保する
     * （docs/data-model.md ⑥）。
     *
     * Seeder投入済みの状態そのものが検証対象のため、実行（Act）にあたる操作を持たない。
     */
    public function test_seeded_titles_have_no_duplicate_series_thresholds(): void
    {
        // Arrange
        $this->seed();

        $series = Title::query()
            ->get(['care_action_id', 'condition_type', 'condition_value'])
            ->map(fn (Title $title): string => sprintf(
                '%s/%d/%d',
                $title->care_action_id ?? 'overall',
                $title->condition_type->value,
                $title->condition_value,
            ));

        // Assert
        $this->assertSame($series->count(), $series->unique()->count());
    }
}
