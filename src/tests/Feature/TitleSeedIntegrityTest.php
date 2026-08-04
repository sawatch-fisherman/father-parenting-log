<?php

namespace Tests\Feature;

use App\Enums\TitleConditionType;
use App\Enums\TitleGrade;
use App\Models\CareAction;
use App\Models\Title;
use App\Support\TitleId;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use ReflectionClass;
use Tests\TestCase;

/**
 * `TitleSeeder`が投入する称号マスタの構造的な整合性を検証する。
 *
 * 称号は育児行動ごとに銅・銀・金の3段階を必ず揃える設計なので、育児行動を追加したのに
 * 称号を足し忘れる／等級としきい値の対応が逆転する、といった崩れをここで検知する。
 *
 * 本クラスはいずれも「Seeder投入済みの状態」そのものが検証対象で、状態を変える操作を行わない。
 * そのため実行（Act）にあたるブロックを持たず、`$this->seed()` と検証用の読み出しがArrangeにあたる。
 *
 * @see docs/decisions.md §1.3「称号の提示順・等級・一覧表示」
 */
class TitleSeedIntegrityTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * 標準17個の育児行動それぞれに、銅・銀・金のCount称号が1件ずつ揃っていることを検証する。
     */
    public function test_every_standard_care_action_has_bronze_silver_and_gold_count_titles(): void
    {
        // Arrange
        $this->seed();

        $careActions = CareAction::query()->whereNull('user_id')->get();

        // Assert
        $this->assertCount(17, $careActions);

        foreach ($careActions as $careAction) {
            foreach (TitleGrade::cases() as $grade) {
                $this->assertSame(1, Title::query()
                    ->where('care_action_id', $careAction->id)
                    ->where('condition_type', TitleConditionType::Count)
                    ->where('grade', $grade)
                    ->count(), "「{$careAction->name}」に{$grade->label()}のCount称号がちょうど1件ない");
            }
        }
    }

    /**
     * 全体合計（`care_action_id IS NULL`）のCount称号が銅・銀・金の3段階そろっていることを検証する。
     *
     * この系統は、ユーザーカスタム育児行動（Phase 2以降）の積み上げを受け止める唯一のCount系統。
     * カスタム育児行動そのものには専用の称号を作らない設計（docs/data-model.md ⑥）なので、
     * ここが欠けると「自分で作った育児行動をいくら記録しても称号が1件も増えない」状態になる。
     */
    public function test_overall_count_titles_cover_every_grade(): void
    {
        // Arrange
        $this->seed();

        // Assert
        foreach (TitleGrade::cases() as $grade) {
            $this->assertSame(1, Title::query()
                ->whereNull('care_action_id')
                ->where('condition_type', TitleConditionType::Count)
                ->where('grade', $grade)
                ->count(), "全体合計に{$grade->label()}のCount称号がちょうど1件ない");
        }
    }

    /**
     * 全体称号（`care_action_id IS NULL`）が育児行動別の称号より先に提示されることを検証する。
     *
     * 称号図鑑は90件の一覧なので、件数の少ない全体称号を後ろに置くと発見性が失われる
     * （docs/decisions.md §1.3「称号の提示順・等級・一覧表示」）。育児行動別の称号を
     * 追加する際に配列の先頭側へ差し込んでしまう事故をここで検知する。
     */
    public function test_overall_titles_are_presented_before_care_action_titles(): void
    {
        // Arrange
        $this->seed();

        $lastOverall = Title::query()->whereNull('care_action_id')->max('sort_order');
        $firstByCareAction = Title::query()->whereNotNull('care_action_id')->min('sort_order');

        // Assert
        $this->assertLessThan($firstByCareAction, $lastOverall, '全体称号が育児行動別の称号より後に提示されている');
    }

    /**
     * 育児行動別の称号が、条件種別ごとに`care_actions.sort_order`（育児行動の表示順）どおりの
     * 提示順で並んでいることを検証する。
     *
     * `titles.id`は永続化された主キーなので称号を後から追加すると末尾に採番するしかないが、
     * `titles.sort_order`は提示順を表す独立したキーなので、追加した称号も配列の正しい位置に
     * 差し込む必要がある（docs/data-model.md ⑥）。両者を混同すると、追加した育児行動の称号だけが
     * S5の獲得モーダルで最後に出るという食い違いが生まれる。
     */
    public function test_care_action_titles_are_presented_in_care_action_display_order(): void
    {
        // Arrange
        $this->seed();

        $displayOrder = CareAction::query()->whereNull('user_id')->pluck('sort_order', 'id');

        /** @var Collection<int, Collection<int, Title>> $seriesByConditionType */
        $seriesByConditionType = Title::query()
            ->whereNotNull('care_action_id')
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (Title $title): int => $title->condition_type->value);

        // Assert
        foreach ($seriesByConditionType as $conditionType => $titles) {
            $presented = $titles
                ->pluck('care_action_id')
                ->unique()
                ->map(fn (int $careActionId): int => (int) $displayOrder[$careActionId])
                ->values()
                ->all();

            $ascending = $presented;
            sort($ascending);

            $this->assertSame(
                $ascending,
                $presented,
                sprintf('condition_type %d の称号が育児行動の表示順に並んでいない', $conditionType),
            );
        }
    }

    /**
     * 系統ごとに、等級が上がるほどしきい値も大きくなっていることを検証する。
     *
     * 等級は`condition_value`から自動導出しない独立した列なので、両者が逆転していても
     * DBは受け付けてしまう（docs/data-model.md ⑥）。系統ごとに 銅 < 銀 < 金 の順で
     * しきい値が上がることをここで担保する（しきい値そのものの重複は
     * TitleUniqueConstraintTest で担保する）。
     */
    public function test_thresholds_increase_with_grade_within_each_series(): void
    {
        // Arrange
        $this->seed();

        /** @var Collection<string, Collection<int, Title>> $series */
        $series = Title::query()->get()->groupBy(fn (Title $title): string => sprintf(
            '%s/%d',
            $title->care_action_id ?? 'overall',
            $title->condition_type->value,
        ));

        // Assert
        foreach ($series as $key => $titles) {
            $thresholds = $titles
                ->sortBy(fn (Title $title): int => $title->grade->value)
                ->pluck('condition_value')
                ->all();

            $ascending = $thresholds;
            sort($ascending);

            $this->assertSame($ascending, $thresholds, "系統 {$key} で等級としきい値の並びが逆転している");
        }
    }

    /**
     * `TitleId`の定数と実際に投入される行が1対1であることを検証する。
     *
     * 定数を足したのにSeederへ書き忘れる／定数値を重複させる、といった取りこぼしを検知する。
     */
    public function test_every_title_id_constant_is_seeded_exactly_once(): void
    {
        // Arrange
        $this->seed();

        $ids = array_values(array_map(
            static fn (mixed $value): int => (int) $value,
            (new ReflectionClass(TitleId::class))->getConstants(),
        ));

        // Assert
        $this->assertSame(count($ids), count(array_unique($ids)), 'TitleId の定数値に重複がある');
        $this->assertSame(count($ids), Title::query()->count(), 'TitleId の定数と titles の行数が一致していない');
        $this->assertSame(count($ids), Title::query()->whereIn('id', $ids)->count(), 'TitleId の定数に対応しない称号行がある');
    }

    /**
     * Count系の称号名は「見習い→職人→名人」の接尾辞と等級が1対1に対応する。
     *
     * 称号名だけで等級が読み取れる状態を保つための検証（docs/features.md「称号一覧」）。
     * Streak系は「{日数表記}連続{短縮ラベル}」の別書式なので対象外。
     */
    public function test_count_title_names_end_with_the_suffix_of_their_grade(): void
    {
        // Arrange
        $this->seed();

        $suffixes = [
            TitleGrade::Bronze->value => '見習い',
            TitleGrade::Silver->value => '職人',
            TitleGrade::Gold->value => '名人',
        ];

        $titles = Title::query()->where('condition_type', TitleConditionType::Count)->get();

        // Assert
        $this->assertNotEmpty($titles);

        foreach ($titles as $title) {
            $this->assertStringEndsWith(
                $suffixes[$title->grade->value],
                $title->name,
                "称号「{$title->name}」の接尾辞が等級（{$title->grade->label()}）と対応していない",
            );
        }
    }
}
