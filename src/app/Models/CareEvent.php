<?php

namespace App\Models;

use Database\Factories\CareEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'care_event_type_id', 'occurred_at', 'memo'])]
class CareEvent extends Model
{
    /** @use HasFactory<CareEventFactory> */
    use HasFactory, HasUlids;

    /**
     * `occurred_at` はミリ秒までのフォーマットを明示する。
     *
     * 素の `datetime` キャストだと書き込み時にグラマ既定の `Y-m-d H:i:s` が使われ、
     * カラムが `DATETIME(3)` でもミリ秒が切り捨てられてしまう。二重送信防止の
     * `UNIQUE(user_id, care_event_type_id, occurred_at)` はクライアントが送るミリ秒精度の
     * タイムスタンプが前提のため、秒に丸まると同一秒内の正当な2件目まで弾いてしまう。
     *
     * @see docs/data-model.md ④ `care_events`
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
     * @return BelongsTo<CareEventType, $this>
     */
    public function careEventType(): BelongsTo
    {
        return $this->belongsTo(CareEventType::class);
    }
}
