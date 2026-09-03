<?php

namespace App\Support;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

/**
 * S12（期間別集計画面）の日/週/月タブが必要とする「7バケットぶんの窓」の算出を1箇所に集約する。
 *
 * タブが選ぶのは「バケットの粒度」であり「1期間の選択」ではない。日タブ＝1日きざみ×7、
 * 週タブ＝1週きざみ×7、月タブ＝1か月きざみ×7で、期間送りはこの窓を7バケットぶんスライドさせる
 * （`docs/decisions.md` §1.3「S12 集計グラフの仕様」）。
 *
 * 週の開始曜日はドキュメント側で明示的に定義されていないため、ここでは月曜始まり（`Carbon::MONDAY`〜
 * `Carbon::SUNDAY`）を採用する。基準日（`base_date`）は「その週／月に属する任意の日」として扱われ、
 * 前後の期間送りは基準日を7週・7か月ぶん動かすだけで正しいバケットに着地する。
 */
final class StatsBucketWindow
{
    public const int BUCKET_COUNT = 7;

    /**
     * 有効なタブ種別（`docs/screens.md` `stats.index`）。
     *
     * @var list<string>
     */
    private const array VALID_TABS = ['day', 'week', 'month', 'all'];

    /**
     * クエリパラメータのタブ種別を正規化する。不正・欠落した値は既定タブ（日タブ）にフォールバックする。
     */
    public static function resolveTab(?string $tab): string
    {
        return in_array($tab, self::VALID_TABS, true) ? $tab : 'day';
    }

    /**
     * クエリパラメータの基準日を正規化する。不正・欠落した値は今日にフォールバックする。
     */
    public static function resolveBaseDate(?string $baseDate): Carbon
    {
        if ($baseDate === null) {
            return Carbon::today();
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $baseDate);
        } catch (InvalidFormatException) {
            // "not-a-date" のようにフォーマットへ全く一致しない文字列は例外を投げる
            // （"2026-13-40" のような暦として無効な値は例外にならず繰り上げて解釈されるため、
            // 下の往復チェックで別途弾く）。
            return Carbon::today();
        }

        if ($parsed === null || $parsed->format('Y-m-d') !== $baseDate) {
            return Carbon::today();
        }

        return $parsed->startOfDay();
    }

    /**
     * 指定タブ・基準日から7バケットぶんの日付範囲と、期間送り（前後）の基準日を算出する。
     * タブは `day`／`week`／`month` のみを受け付ける（`all` タブはバケット構造を持たない）。
     *
     * @return array{buckets: list<array{start: Carbon, end: Carbon}>, prevBaseDate: Carbon, nextBaseDate: Carbon}
     */
    public static function resolve(string $tab, Carbon $baseDate): array
    {
        return match ($tab) {
            'week' => [
                'buckets' => self::weeklyBuckets($baseDate),
                'prevBaseDate' => $baseDate->copy()->subWeeks(self::BUCKET_COUNT),
                'nextBaseDate' => $baseDate->copy()->addWeeks(self::BUCKET_COUNT),
            ],
            'month' => [
                'buckets' => self::monthlyBuckets($baseDate),
                'prevBaseDate' => $baseDate->copy()->subMonths(self::BUCKET_COUNT),
                'nextBaseDate' => $baseDate->copy()->addMonths(self::BUCKET_COUNT),
            ],
            default => [
                'buckets' => self::dailyBuckets($baseDate),
                'prevBaseDate' => $baseDate->copy()->subDays(self::BUCKET_COUNT),
                'nextBaseDate' => $baseDate->copy()->addDays(self::BUCKET_COUNT),
            ],
        };
    }

    /**
     * 基準日を含む1日ぶんのバケットを、過去6日分とあわせて7個（古い→新しい順）で返す。
     *
     * @return list<array{start: Carbon, end: Carbon}>
     */
    private static function dailyBuckets(Carbon $baseDate): array
    {
        $buckets = [];

        for ($i = self::BUCKET_COUNT - 1; $i >= 0; $i--) {
            $start = $baseDate->copy()->subDays($i)->startOfDay();
            $buckets[] = ['start' => $start, 'end' => $start->copy()->endOfDay()];
        }

        return $buckets;
    }

    /**
     * 基準日が属する週（月曜始まり）を含む、過去6週分とあわせて7個（古い→新しい順）のバケットを返す。
     *
     * @return list<array{start: Carbon, end: Carbon}>
     */
    private static function weeklyBuckets(Carbon $baseDate): array
    {
        $buckets = [];

        for ($i = self::BUCKET_COUNT - 1; $i >= 0; $i--) {
            $start = $baseDate->copy()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $buckets[] = ['start' => $start, 'end' => $start->copy()->endOfWeek(Carbon::SUNDAY)];
        }

        return $buckets;
    }

    /**
     * 基準日が属する月を含む、過去6か月分とあわせて7個（古い→新しい順）のバケットを返す。
     *
     * @return list<array{start: Carbon, end: Carbon}>
     */
    private static function monthlyBuckets(Carbon $baseDate): array
    {
        $buckets = [];

        for ($i = self::BUCKET_COUNT - 1; $i >= 0; $i--) {
            $start = $baseDate->copy()->subMonths($i)->startOfMonth();
            $buckets[] = ['start' => $start, 'end' => $start->copy()->endOfMonth()];
        }

        return $buckets;
    }
}
