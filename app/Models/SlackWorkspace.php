<?php

namespace App\Models;

use App\Traits\HasGroupRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'agent_id', 'status', 'slack_app_id', 'slack_client_id',
    'slack_client_secret', 'slack_signing_secret', 'slack_verification_token',
    'slack_bot_token', 'slack_team_id', 'slack_team_name', 'slack_bot_user_id',
    'storage_slug', 'manifest_json', 'oauth_response_json', 'metadata_json',
    'icon_path',
])]
class SlackWorkspace extends BaseModel
{
    use HasFactory;
    use HasGroupRole;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SlackEvent::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AgentDeployment::class, 'agent_id', 'agent_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SlackWorkspace $workspace) {
            if (empty($workspace->storage_slug)) {
                $workspace->storage_slug = static::generateUniqueStorageSlug();
            }
        });
    }

    public static function generateUniqueStorageSlug(): string
    {
        return 'slack-' . Str::slug(Str::uuid()->toString());
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'slack_client_secret' => 'encrypted',
            'slack_signing_secret' => 'encrypted',
            'slack_verification_token' => 'encrypted',
            'slack_bot_token' => 'encrypted',
            'manifest_json' => 'array',
            'oauth_response_json' => 'array',
            'metadata_json' => 'array',
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && filled($this->slack_bot_token);
    }

    public function getEventsRequestUrl(): string
    {
        return route('slack.events', ['workspace' => $this->id]);
    }

    public function getOauthRedirectUrl(): string
    {
        return route('slack.oauth.callback');
    }
}
