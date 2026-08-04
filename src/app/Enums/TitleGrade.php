<?php

namespace App\Enums;

/**
 * 称号の等級（銅／銀／金）。金がその系統の到達点にあたる。
 *
 * 称号の段階を表す唯一の表現で、称号名に`Lv.N`のようなレベル表記は使わない。
 * `Lv`が青天井なのに対し、育児には卒業（未就学児まで）という終わりがあるため、
 * 上限のある等級のほうが有限期間のゴール設定として整合する。
 *
 * @see docs/decisions.md §1.3「称号の提示順・等級・一覧表示」
 * @see docs/data-model.md ⑥ `titles`
 */
enum TitleGrade: int
{
    case Bronze = 0;

    case Silver = 1;

    case Gold = 2;

    public function label(): string
    {
        return match ($this) {
            self::Bronze => '銅',
            self::Silver => '銀',
            self::Gold => '金',
        };
    }
}
