<?php

namespace App\Policies;

use App\Models\CareLog;
use App\Models\User;

/**
 * 育児ログの事後操作（S11の表示・日時／メモの変更・削除）の認可。
 *
 * `{care_log}` は URL に ID 付きで現れる唯一のリソースのため、他人の記録IDを直接叩かれても
 * 触れないよう所有者チェックが必須になる（[docs/screens.md](../../../docs/screens.md) 補足）。
 * あわせて「遡り操作は直近7日まで」の締めも、`update`・`delete` の両方でここに寄せる
 * （[docs/decisions.md](../../../docs/decisions.md) §1.3）。
 *
 * 締めの判定をアプリ層のバリデーション（`UpdateCareLogRequest`）だけに置かないのは、
 * そちらが見るのは「変更後の日時」であって「変更対象の記録がまだ操作可能か」ではないため。
 * 8日前の記録の日時を今日に付け替える操作は、バリデーションだけでは素通りしてしまう。
 */
class CareLogPolicy
{
    /**
     * 育児ログを編集（S11の表示・`occurred_at`／`memo`の更新）できるかを判定する。
     */
    public function update(User $user, CareLog $careLog): bool
    {
        return $this->isOwnedAndStillOpen($user, $careLog);
    }

    /**
     * 育児ログを削除できるかを判定する。
     */
    public function delete(User $user, CareLog $careLog): bool
    {
        return $this->isOwnedAndStillOpen($user, $careLog);
    }

    /**
     * 自分の記録であり、かつ遡り操作の締め（「`backdate_days`日前の00:00」）より後かを判定する。
     */
    private function isOwnedAndStillOpen(User $user, CareLog $careLog): bool
    {
        return $careLog->user_id === $user->id
            && $careLog->isWithinBackdateWindow();
    }
}
