<?php

namespace App\Models;

use Database\Factories\AgentExecutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'agent_id', 'user_id', 'input', 'context', 'status',
    'progress', 'started_at', 'completed_at', 'error_message', 'config_snapshot'
])]
class AgentExecution extends BaseModel
{
    use HasFactory;

    /** @use HasFactory<AgentExecutionFactory> */
    protected static function newFactory(): AgentExecutionFactory
    {
        return AgentExecutionFactory::new();
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(AgentOutput::class);
    }
}
