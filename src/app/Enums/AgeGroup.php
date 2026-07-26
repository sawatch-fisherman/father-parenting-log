<?php

namespace App\Enums;

enum AgeGroup: int
{
    case Unanswered = 0;
    case Twenties = 1;
    case Thirties = 2;
    case Forties = 3;
    case FiftiesOrOver = 4;

    public function label(): string
    {
        return match ($this) {
            self::Unanswered => '未回答',
            self::Twenties => '20代',
            self::Thirties => '30代',
            self::Forties => '40代',
            self::FiftiesOrOver => '50代以上',
        };
    }
}
