<?php

namespace App\Models;

use App\Enums\TitleConditionType;
use App\Enums\TitleGrade;
use Database\Factories\TitleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['care_action_id', 'name', 'grade', 'condition_type', 'condition_value', 'sort_order'])]
class Title extends Model
{
    /** @use HasFactory<TitleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade' => TitleGrade::class,
            'condition_type' => TitleConditionType::class,
        ];
    }

    /**
     * @return BelongsTo<CareAction, $this>
     */
    public function careAction(): BelongsTo
    {
        return $this->belongsTo(CareAction::class);
    }

    /**
     * @return HasMany<UserTitle, $this>
     */
    public function userTitles(): HasMany
    {
        return $this->hasMany(UserTitle::class);
    }
}
