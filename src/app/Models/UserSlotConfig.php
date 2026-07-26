<?php

namespace App\Models;

use Database\Factories\UserSlotConfigFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'slot_position', 'care_event_type_id'])]
class UserSlotConfig extends Model
{
    /** @use HasFactory<UserSlotConfigFactory> */
    use HasFactory, HasUlids;

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
