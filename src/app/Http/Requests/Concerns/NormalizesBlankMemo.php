<?php

namespace App\Http\Requests\Concerns;

/**
 * `memo` の未入力（空文字）を`null`に正規化する処理を、`StoreCareLogRequest`（M4）と
 * `UpdateCareLogRequest`（M6）で共有する。
 *
 * 空文字のまま保存すると、自由入力欄に意図せず個人情報を書いた場合の自己訂正手段
 * （空にして保存すればメモを削除できる。[docs/privacy.md](../../../../docs/privacy.md) §9）が
 * 見た目上は消えても実際には空文字が残る形で壊れる。片方だけ直すと「S10では空文字が
 * `null`になるがS11では残る」といったズレが生まれるため、`ValidatesOccurredAt`が
 * `occurred_at`の範囲ルールを共有しているのと同じ理由でここに寄せる。
 */
trait NormalizesBlankMemo
{
    /**
     * `memo`が空文字なら`null`に正規化する。
     */
    protected function normalizeBlankMemo(): void
    {
        $this->merge([
            'memo' => $this->memo === '' ? null : $this->memo,
        ]);
    }
}
