<?php

namespace App\Enums;

enum TitleConditionType: int
{
    case Count = 0;
    case Streak = 1;

    public function label(): string
    {
        return match ($this) {
            self::Count => '累計回数',
            self::Streak => '連続日数',
        };
    }
}
