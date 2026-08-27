<?php

namespace App\Services;

use App\Enums\TitleConditionType;
use App\Models\CareLog;
use App\Models\Title;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * 育児ログ登録の結果として、Count・Streak両方式の称号を同期判定し新規獲得分を確定する。
 *
 * 対象範囲はいずれも`titles.care_action_id`で表現する（NULL＝全育児行動、値あり＝その育児行動のみ）。
 * 新規達成のみ`user_titles`を作成し（`UNIQUE(user_id, title_id)`）、`achievement_text`はここで
 * `lang/ja/titles.php`から現在のロケール向けに組み立てて返す（X投稿文＝S6のサーバー往復なしの原則を
 * 保つため。`CareLogController@store`のレスポンスに乗るだけで追加リクエストは発生しない）。
 *
 * @see docs/implementation-plan.md「M5 称号（S5, S6）」
 * @see docs/decisions.md §1.3「X投稿文（S6）の達成内容の一文は、サーバー側で組み立てて返す」
 */
class TitleGrantService
{
    /**
     * 保存済みの育児ログを起点に称号を判定し、新規獲得分を`user_titles`へ確定する。
     *
     * @return list<array{name: string, achievement_text: string}>
     */
    public function grant(User $user, CareLog $careLog): array
    {
        $candidates = Title::query()
            ->with('careAction')
            ->where(fn ($query) => $query
                ->whereNull('care_action_id')
                ->orWhere('care_action_id', $careLog->care_action_id))
            ->whereDoesntHave('userTitles', fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('sort_order')
            ->get();

        $granted = [];

        foreach ($candidates as $title) {
            $achievedValue = $this->achievedValue($user, $title, $careLog);

            if ($achievedValue < $title->condition_value) {
                continue;
            }

            $user->userTitles()->create([
                'title_id' => $title->id,
                'unlocked_at' => now(),
            ]);

            $granted[] = [
                'name' => $title->name,
                'achievement_text' => $this->achievementText($title),
            ];
        }

        return $granted;
    }

    /**
     * 称号の条件種別に応じて、現時点の達成値（累計回数 または 起点日からの連続日数）を返す。
     */
    private function achievedValue(User $user, Title $title, CareLog $careLog): int
    {
        return match ($title->condition_type) {
            TitleConditionType::Count => $this->totalCount($user, $title->care_action_id),
            TitleConditionType::Streak => $this->streakDays($user, $title->care_action_id, $careLog->occurred_at->copy()->startOfDay()),
        };
    }

    /**
     * 対象範囲（`care_action_id`。NULLなら全育児行動）の累計記録回数を返す。
     */
    private function totalCount(User $user, ?int $careActionId): int
    {
        return $user->careLogs()
            ->when($careActionId !== null, fn ($query) => $query->where('care_action_id', $careActionId))
            ->count();
    }

    /**
     * 対象範囲でのDISTINCTな記録日（JST暦日）を求め、`$anchorDate`を起点に過去へ向かって
     * 何日連続で記録があるかを数える。
     *
     * 「今回保存した育児ログの日付を起点に」連続日数を計算する仕様（docs/decisions.md §1.3）のため、
     * 起点日より新しい記録日があっても数えには含めない（バックデート入力が既存の連続記録の
     * 隙間を埋めた場合、起点日から見た連続日数のみを判定対象にする）。
     */
    private function streakDays(User $user, ?int $careActionId, Carbon $anchorDate): int
    {
        /** @var Collection<int, string> $recordedDays */
        $recordedDays = $user->careLogs()
            ->when($careActionId !== null, fn ($query) => $query->where('care_action_id', $careActionId))
            ->selectRaw('DISTINCT DATE(occurred_at) as day')
            ->pluck('day');

        $recordedDaySet = $recordedDays->flip();

        $streak = 0;
        $cursor = $anchorDate->copy();

        while ($recordedDaySet->has($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    /**
     * 称号の`condition_type`／`care_action_id`の有無に応じた4パターンで達成内容の一文を組み立てる。
     */
    private function achievementText(Title $title): string
    {
        $value = $title->condition_value;

        return match ($title->condition_type) {
            TitleConditionType::Count => $title->careAction === null
                ? __('titles.achievement_count_overall', ['value' => $value])
                : __('titles.achievement_count_action', ['value' => $value, 'action' => $title->careAction->name]),
            TitleConditionType::Streak => $title->careAction === null
                ? __('titles.achievement_streak_overall', ['value' => $value])
                : __('titles.achievement_streak_action', ['value' => $value, 'action' => $title->careAction->name]),
        };
    }
}
