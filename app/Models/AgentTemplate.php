<?php

namespace App\Models;

use Database\Factories\AgentTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'description', 'system_prompt', 'user_context',
    'config_defaults', 'env_defaults', 'tool_definitions'
])]
class AgentTemplate extends BaseModel
{
    use HasFactory;

    /** @use HasFactory<AgentTemplateFactory> */
    protected static function newFactory(): AgentTemplateFactory
    {
        return AgentTemplateFactory::new();
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }
}
