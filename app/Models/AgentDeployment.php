<?php

namespace App\Models;

use App\Traits\HasGroupRole;
use Database\Factories\AgentDeploymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'group_id', 'agent_id', 'name', 'slug', 'description', 'image', 'namespace',
    'model_config', 'soul_markdown', 'agents_markdown', 'config_yaml',
    'env_variables', 'secrets', 'resource_limits', 'replicas', 'domain',
    'status', 'yaml_snapshot', 'is_active', 'deployed_at', 'last_status_check_at',
])]
class AgentDeployment extends BaseModel
{
    use HasFactory;
    use HasGroupRole;

    /** @use HasFactory<AgentDeploymentFactory> */
    protected static function newFactory(): AgentDeploymentFactory
    {
        return AgentDeploymentFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AgentExecution::class, 'agent_id', 'agent_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'model_config' => 'array',
            'env_variables' => 'array',
            'secrets' => 'array',
            'resource_limits' => 'array',
            'replicas' => 'integer',
            'is_active' => 'boolean',
            'deployed_at' => 'datetime',
            'last_status_check_at' => 'datetime',
        ]);
    }
}
