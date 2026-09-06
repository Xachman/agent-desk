<?php

namespace App\Models;

use Database\Factories\AgentTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'description', 'system_prompt', 'user_context',
    'config', 'env', 'tool_definitions'
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
        return $this->hasMany(Agent::class, 'template_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'config' => 'array',
            'env' => 'array',
            'tool_definitions' => 'array',
        ]);
    }
}
