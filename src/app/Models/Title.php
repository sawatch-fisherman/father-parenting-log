<?php

namespace App\Models;

use App\Enums\TitleConditionType;
use Database\Factories\TitleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['care_event_type_id', 'name', 'condition_type', 'condition_value', 'sort_order'])]
class Title extends Model
{
    /** @use HasFactory<TitleFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition_type' => TitleConditionType::class,
        ];
    }

    /**
     * @return BelongsTo<CareEventType, $this>
     */
    public function careEventType(): BelongsTo
    {
        return $this->belongsTo(CareEventType::class);
    }

    /**
     * @return HasMany<UserTitle, $this>
     */
    public function userTitles(): HasMany
    {
        return $this->hasMany(UserTitle::class);
    }
}
