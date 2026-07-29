<?php

namespace App\Models;

use Database\Factories\CareEventTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'sort_order'])]
class CareEventType extends Model
{
    /** @use HasFactory<CareEventTypeFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CareEvent, $this>
     */
    public function careEvents(): HasMany
    {
        return $this->hasMany(CareEvent::class);
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
}
