<?php

namespace App\Models;

use Database\Factories\CareLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 育児ログ。父親が行った育児行動そのものを1行1件で記録する、TotoOpsの中核テーブル。
 */
#[Fillable(['user_id', 'care_action_id', 'occurred_at', 'memo'])]
class CareLog extends Model
{
    /** @use HasFactory<CareLogFactory> */
    use HasFactory, HasUlids;

    /**
     * `occurred_at` はミリ秒までのフォーマットを明示する。
     *
     * 素の `datetime` キャストだと書き込み時にグラマ既定の `Y-m-d H:i:s` が使われ、
     * カラムが `DATETIME(3)` でもミリ秒が切り捨てられてしまう。二重送信防止の
     * `UNIQUE(user_id, care_action_id, occurred_at)` はクライアントが送るミリ秒精度の
     * タイムスタンプが前提のため、秒に丸まると同一秒内の正当な2件目まで弾いてしまう。
     *
     * @see docs/data-model.md ④ `care_logs`
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime:Y-m-d H:i:s.v',
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
