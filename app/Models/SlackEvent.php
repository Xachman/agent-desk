<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'slack_workspace_id', 'agent_execution_id', 'slack_event_id', 'type',
    'subtype', 'channel_id', 'user_id', 'text', 'payload', 'status',
    'error_message', 'response_payload', 'processed_at',
])]
class SlackEvent extends BaseModel
{
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(SlackWorkspace::class, 'slack_workspace_id');
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AgentExecution::class, 'agent_execution_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'payload' => 'array',
            'response_payload' => 'array',
            'processed_at' => 'datetime',
        ]);
    }

    public function markProcessing(): bool
    {
        return $this->update(['status' => 'processing']);
    }

    public function markCompleted(?array $responsePayload = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'response_payload' => $responsePayload,
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $error): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'processed_at' => now(),
        ]);
    }
}
