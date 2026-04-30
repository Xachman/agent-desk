<?php

namespace App\Models;

use Database\Factories\AgentOutputFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'execution_id', 'file_type', 'file_path', 'content', 'size_bytes'
])]
class AgentOutput extends BaseModel
{
    use HasFactory;

    /** @use HasFactory<AgentOutputFactory> */
    protected static function newFactory(): AgentOutputFactory
    {
        return AgentOutputFactory::new();
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AgentExecution::class);
    }
}
