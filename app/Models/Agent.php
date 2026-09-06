<?php

namespace App\Models;

use App\Traits\HasGroupRole;
use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id', 'group_id', 'template_id', 'name', 'description',
    'agent_config', 'env_variables', 'config_template', 'is_active'
])]
class Agent extends BaseModel
{
    use HasFactory;
    use HasGroupRole;

    /** @use HasFactory<AgentFactory> */
    protected static function newFactory(): AgentFactory
    {
        return AgentFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AgentTemplate::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AgentExecution::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(AgentOutput::class);
    }

    public function secrets(): HasMany
    {
        return $this->hasMany(AgentSecret::class);
    }

    public function deployment(): HasOne
    {
        return $this->hasOne(AgentDeployment::class);
    }
}
