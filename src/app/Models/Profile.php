<?php

namespace App\Models;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * アクセサ／ミューテータ（`ageGroup()`／`childAgeGroup()`）で定義したEnum型はlarastanが
 * 型推論できないため、`@property` で明示する（`app/`配下のみphpstan level 8の対象）。
 *
 * @property AgeGroup $age_group
 * @property ChildAgeGroup $child_age_group
 */
#[Fillable(['user_id', 'nickname', 'age_group', 'child_age_group'])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'graduated_at' => 'datetime',
        ];
    }

    /**
     * 年代を保持する。`casts()`によるEnumキャストは値が`null`だとスキップされる仕様のため、
     * `age_group`カラムがNOT NULLであることを守れるよう、`null`は`Unanswered`に正規化する。
     *
     * @return Attribute<AgeGroup, int|AgeGroup|null>
     */
    protected function ageGroup(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): AgeGroup => AgeGroup::from($value),
            set: fn (int|AgeGroup|null $value): AgeGroup => match (true) {
                $value instanceof AgeGroup => $value,
                $value === null => AgeGroup::Unanswered,
                default => AgeGroup::from($value),
            },
        );
    }

    /**
     * いちばん下のお子さんの年齢帯を保持する。挙動は{@see self::ageGroup()}と同じ。
     *
     * @return Attribute<ChildAgeGroup, int|ChildAgeGroup|null>
     */
    protected function childAgeGroup(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): ChildAgeGroup => ChildAgeGroup::from($value),
            set: fn (int|ChildAgeGroup|null $value): ChildAgeGroup => match (true) {
                $value instanceof ChildAgeGroup => $value,
                $value === null => ChildAgeGroup::Unanswered,
                default => ChildAgeGroup::from($value),
            },
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
