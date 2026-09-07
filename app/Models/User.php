<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (empty($user->username)) {
                $user->username = static::generateUniqueUsername($user->name);
            }
        });

        static::created(function (User $user) {
            $user->personalGroup()->firstOrCreate(
                ['owner_id' => $user->id],
                [
                    'name' => $user->name ?: $user->username,
                    'slug' => $user->username,
                    'description' => 'Personal workspace for ' . ($user->name ?: $user->username),
                ]
            );
        });
    }

    public static function generateUniqueUsername(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name) ?: 'user';
        $base = substr($base, 0, 50);
        $username = $base;
        $counter = 1;

        while (static::where('username', $username)->exists()) {
            $suffix = '-' . $counter++;
            $username = substr($base, 0, 50 - strlen($suffix)) . $suffix;
        }

        return $username;
    }

    public function personalGroup(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Group::class, 'owner_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    public function groupRole(Group $group): ?string
    {
        if ($group->owner_id === $this->id) {
            return 'owner';
        }

        $pivot = $this->groups()
            ->wherePivot('group_id', $group->id)
            ->first()?->pivot;

        return $pivot?->role;
    }

    public function isGroupOwner(Group $group): bool
    {
        return $group->owner_id === $this->id;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
