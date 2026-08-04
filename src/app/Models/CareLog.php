<?php

namespace App\Models;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use Database\Factories\CareLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 育児ログ。父親が行った育児行動そのものを1行1件で記録する、TotoOpsの中核テーブル。
 */
#[Fillable(['user_id', 'care_action_id', 'occurred_at', 'age_group', 'child_age_group', 'memo'])]
class CareLog extends Model
{
    /** @use HasFactory<CareLogFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'age_group' => AgeGroup::class,
            'child_age_group' => ChildAgeGroup::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CareAction, $this>
     */
    public function careAction(): BelongsTo
    {
        return $this->belongsTo(CareAction::class);
    }
}
