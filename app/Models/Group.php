<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'owner_id'])]
class Group extends BaseModel
{
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'member');
    }

    public function admins(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'admin');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(AgentTemplate::class, 'group_id');
    }

    public function secrets(): HasMany
    {
        return $this->hasMany(AgentSecret::class, 'group_id');
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(AgentDeployment::class, 'group_id');
    }

    public function addMember(User $user, string $role = 'member'): void
    {
        \Illuminate\Support\Facades\DB::table('group_user')->updateOrInsert(
            ['group_id' => $this->id, 'user_id' => $user->id],
            ['role' => $role, 'created_at' => now(), 'updated_at' => now()]
        );

        $this->load('users');
    }

    public function removeMember(User $user): void
    {
        $this->users()->detach($user->id);
    }

    public function memberRole(User $user): ?string
    {
        $pivot = $this->users()
            ->wherePivot('user_id', $user->id)
            ->first()?->pivot;

        return $pivot?->role;
    }

    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Group $group) {
            if (!$group->slug) {
                $group->slug = Str::slug($group->name);
            }
        });
    }
}
