<?php

namespace App\Services;

use App\Enums\TitleConditionType;
use App\Models\CareLog;
use App\Models\Title;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

/**
 * 育児ログ登録の結果として、Count・Streak両方式の称号を同期判定し新規獲得分を確定する。
 *
 * 対象範囲はいずれも`titles.care_action_id`で表現する（NULL＝全育児行動、値あり＝その育児行動のみ）。
 * 新規達成のみ`user_titles`を作成し（`UNIQUE(user_id, title_id)`）、`achievement_text`はここで
 * `lang/ja/titles.php`から現在のロケール向けに組み立てて返す（X投稿文＝S6のサーバー往復なしの原則を
 * 保つため。`CareLogController@store`のレスポンスに乗るだけで追加リクエストは発生しない）。
 *
 * **`care_logs`の保存とはトランザクションを束ねない**：`care_logs`は`CareLogController@store`で
 * 既にCOMMIT済みの状態でこのサービスが呼ばれる。「記録の保存」を「称号の付与」より優先する
 * プロダクト方針（叱責ではなく振り返り。docs/concept.md）に基づき、称号付与側の失敗が
 * 育児ログの保存を巻き戻す・失敗させることは意図的に避ける（呼び出し側の
 * `CareLogController@store`で予期しない例外を握りつぶしてログに残す設計とセットで機能する）。
 * 同時リクエストによる`UNIQUE(user_id, title_id)`違反（真の競合。下記`grant()`参照）だけは
 * このサービス内で個別に吸収し、他の称号の付与判定は継続する。
 *
 * @see docs/implementation-plan.md「M5 称号（S5, S6）」
 * @see docs/decisions.md §1.3「X投稿文（S6）の達成内容の一文は、サーバー側で組み立てて返す」
 * @see review-results/pr-11-review.md Medium「称号付与の途中失敗時に、記録は保存済みなのに500になる」
 */
class TitleGrantService
{
    /**
     * 保存済みの育児ログを起点に称号を判定し、新規獲得分を`user_titles`へ確定する。
     *
     * 同じスコープ（`care_action_id`の有無）に属する称号は達成値（累計回数・連続日数）が
     * 共通のため、候補称号1件ごとに集計クエリを打ち直さずスコープ単位でメモ化する
     * （最大4スコープ＝全体Count・全体Streak・育児行動別Count・育児行動別Streak）。
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

        $anchorDate = $careLog->occurred_at->copy()->startOfDay();

        /** @var array<int|string, int> $countByScope */
        $countByScope = [];
        /** @var array<int|string, int> $streakByScope */
        $streakByScope = [];

        $granted = [];

        foreach ($candidates as $title) {
            // `care_action_id`（int|null）をそのまま配列キーにはできない（PHPはnullキーを
            // 空文字列に丸めるため、他のスコープと衝突しうる）。'overall'で明示的に区別する。
            $scopeKey = $title->care_action_id ?? 'overall';

            $achievedValue = match ($title->condition_type) {
                TitleConditionType::Count => $countByScope[$scopeKey] ??= $this->totalCount($user, $title->care_action_id),
                TitleConditionType::Streak => $streakByScope[$scopeKey] ??= $this->streakDays($user, $title->care_action_id, $anchorDate),
            };

            if ($achievedValue < $title->condition_value) {
                continue;
            }

            try {
                $user->userTitles()->create([
                    'title_id' => $title->id,
                    'unlocked_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                // 事前の`whereDoesntHave`はあくまでチェック時点のスナップショットで、同一ユーザーの
                // 別リクエストがほぼ同時に同じ称号のしきい値を跨いだ場合はすり抜けうる
                // （`UNIQUE(user_id, title_id)`。0001_01_01_000008_create_user_titles_table.php）。
                // 二重付与ではなく正常系（付与自体は競合先のリクエストで完結済み）として扱い、
                // このリクエストのレスポンスには含めず、他の候補称号の判定は継続する。
                continue;
            }

            $granted[] = [
                'name' => $title->name,
                'achievement_text' => $this->achievementText($title),
            ];
        }

        return $granted;
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
