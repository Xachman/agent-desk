<?php

namespace App\Models;

use App\Traits\HasGroupRole;
use Database\Factories\AgentTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'group_id', 'user_id', 'name', 'description', 'system_prompt', 'user_context',
    'config', 'env', 'tool_definitions'
])]
class AgentTemplate extends BaseModel
{
    use HasFactory;
    use HasGroupRole;

    /** @use HasFactory<AgentTemplateFactory> */
    protected static function newFactory(): AgentTemplateFactory
    {
        return AgentTemplateFactory::new();
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'template_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
