<?php

namespace App\Models;

use Database\Factories\AgentSecretFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'agent_id', 'name', 'kubernetes_secret_name', 'key', 'value', 'is_active'
])]
class AgentSecret extends BaseModel
{
    use HasFactory;

    /** @use HasFactory<AgentSecretFactory> */
    protected static function newFactory(): AgentSecretFactory
    {
        return AgentSecretFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
