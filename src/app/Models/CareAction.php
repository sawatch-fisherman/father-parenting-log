<?php

namespace App\Models;

use App\Support\CareActionId;
use Database\Factories\CareActionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 育児行動（おむつ交換・寝かしつけ 等）。
 *
 * TotoOps定義の標準17行（`user_id IS NULL`）とユーザーカスタム行（Phase 2以降）を
 * 同一テーブルで管理する。主キーは連番で、標準行の予約域は
 * {@see CareActionId::CUSTOM_ID_FLOOR} を参照。
 */
#[Fillable(['user_id', 'name', 'sort_order'])]
class CareAction extends Model
{
    /** @use HasFactory<CareActionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CareLog, $this>
     */
    public function careLogs(): HasMany
    {
        return $this->hasMany(CareLog::class);
    }

    /**
     * @return HasMany<UserSlotConfig, $this>
     */
    public function userSlotConfigs(): HasMany
    {
        return $this->hasMany(UserSlotConfig::class);
    }

    /**
     * @return HasMany<Title, $this>
     */
    public function titles(): HasMany
    {
        return $this->hasMany(Title::class);
    }

    /**
     * 自分が閲覧・記録可能な育児行動（TotoOps標準行＋自分のカスタム行）に絞り込む。
     *
     * `StoreCareLogRequest`・`CareLogController@create`・`CareActionController@other` の
     * 3箇所が同じ「標準行 or 自分のカスタム行」判定を必要とするため、個別に書かずここに集約する。
     *
     * @param  Builder<CareAction>  $query
     * @return Builder<CareAction>
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        return $query->where(
            fn (Builder $q) => $q->whereNull('user_id')->orWhere('user_id', $user->id)
        );
    }
}
