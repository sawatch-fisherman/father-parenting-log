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

    public function test_duplicate_threshold_within_the_same_series_is_rejected(): void
    {
        $careAction = CareAction::factory()->create();

        Title::factory()->create([
            'care_action_id' => $careAction->id,
            'condition_type' => TitleConditionType::Count,
            'condition_value' => 10,
        ]);

        $this->expectException(QueryException::class);

        Title::factory()->create([
            'care_action_id' => $careAction->id,
            'condition_type' => TitleConditionType::Count,
            'condition_value' => 10,
        ]);
    }

    /**
     * 系統は`care_action_id`と`condition_type`の組で決まるため、条件種別が違えば
     * しきい値が同じでも別系統として共存できる（docs/data-model.md ⑥）。
     */
    public function test_same_threshold_is_allowed_for_a_different_condition_type(): void
    {
        $careAction = CareAction::factory()->create();

        Title::factory()->create([
            'care_action_id' => $careAction->id,
            'condition_type' => TitleConditionType::Count,
            'condition_value' => 10,
        ]);

        Title::factory()->create([
            'care_action_id' => $careAction->id,
            'condition_type' => TitleConditionType::Streak,
            'condition_value' => 10,
        ]);

        $this->assertSame(2, Title::query()->where('care_action_id', $careAction->id)->count());
    }

    /**
     * 全体合計称号（`care_action_id IS NULL`）はMySQLがUNIQUE内のNULL同士を別物として扱うため
     * DBのUNIQUE制約では重複を弾けない。Seeder固定マスタ側で重複が起きていないことをここで担保する
     * （docs/data-model.md ⑥）。
     */
    public function test_seeded_titles_have_no_duplicate_series_thresholds(): void
    {
        $this->seed();

        $series = Title::query()
            ->get(['care_action_id', 'condition_type', 'condition_value'])
            ->map(fn (Title $title): string => sprintf(
                '%s/%d/%d',
                $title->care_action_id ?? 'overall',
                $title->condition_type->value,
                $title->condition_value,
            ));

        $this->assertSame($series->count(), $series->unique()->count());
    }
}
