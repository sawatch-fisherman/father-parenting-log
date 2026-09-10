<?php

namespace App\Support;

use App\Models\User;

/**
 * ピン留め済みの育児行動を `slot_position`（1〜8）順の配列に組み立てる。
 *
 * S3（記録画面）・S9（ピン留め設定画面）の両方が同じ「行が無い位置は空きスロット（`null`）」
 * という組み立てを必要とするため、個別に書かず集約する（`data-model.md` ⑤）。
 */
final class PinnedSlots
{
    /**
     * ピン留めの最大枠数（`slot_position` の上限）。`UpdateSlotConfigRequest` の
     * `max`／`between` ルールもここを参照し、枠数を変える判断をした際に片方だけ
     * 直して食い違う余地を無くす（`CareLogWindow::backdateFloor()`と同じ理由）。
     */
    public const int MAX_SLOTS = 8;

    /**
     * 指定ユーザーのピン留めを `slot_position` 順の{@see self::MAX_SLOTS}要素配列で返す。
     *
     * @return array<int, array{careActionId: int, name: string|null}|null>
     */
    public static function forUser(User $user): array
    {
        $slotConfigsByPosition = $user->userSlotConfigs()
            ->with('careAction')
            ->orderBy('slot_position')
            ->get()
            ->keyBy('slot_position');

        return collect(range(1, self::MAX_SLOTS))
            ->map(function (int $position) use ($slotConfigsByPosition): ?array {
                $slotConfig = $slotConfigsByPosition->get($position);

                if ($slotConfig === null) {
                    return null;
                }

                return [
                    'careActionId' => $slotConfig->care_action_id,
                    'name' => $slotConfig->careAction?->name,
                ];
            })
            ->values()
            ->all();
    }
}
