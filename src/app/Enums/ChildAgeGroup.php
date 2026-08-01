<?php

namespace App\Enums;

/**
 * 子どもの年齢帯。
 *
 * 子どもが複数いる場合も「いちばん下の子（末子）」の年齢帯1つのみを保持する
 * （複数選択・子どもごとの管理は採用しない。docs/decisions.md §1.1）。
 */
enum ChildAgeGroup: int
{
    case Unanswered = 0;
    case Zero = 1;
    case One = 2;
    case Two = 3;
    case Three = 4;
    case FourToSix = 5;

    public function label(): string
    {
        return match ($this) {
            self::Unanswered => '未回答',
            self::Zero => '0歳',
            self::One => '1歳',
            self::Two => '2歳',
            self::Three => '3歳',
            self::FourToSix => '4〜6歳',
        };
    }
}
