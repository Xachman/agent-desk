<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'config_token', 'refresh_token', 'config_token_expires_at',
    'team_id', 'user_id', 'is_active',
])]
class SlackPlatformConfig extends BaseModel
{
    public function workspaces(): HasMany
    {
        return $this->hasMany(SlackWorkspace::class);
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'config_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'config_token_expires_at' => 'datetime',
            'is_active' => 'boolean',
        ]);
    }

    public function isTokenExpired(): bool
    {
        if (!$this->config_token_expires_at) {
            return true;
        }

        return $this->config_token_expires_at->subMinutes(5)->isPast();
    }
}
