<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['provider', 'provider_id'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable;

    /**
     * @return HasOne<Profile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
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
     * @return HasMany<UserTitle, $this>
     */
    public function userTitles(): HasMany
    {
        return $this->hasMany(UserTitle::class);
    }
}
