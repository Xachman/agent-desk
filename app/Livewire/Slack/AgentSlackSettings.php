<?php

namespace App\Livewire\Slack;

use App\Models\Agent;
use App\Models\SlackWorkspace;
use App\Services\SlackAppProvisioningService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AgentSlackSettings extends Component
{
    public Agent $agent;

    public ?string $customName = null;
    public ?string $customDescription = null;

    public function mount(Agent $agent): void
    {
        $this->agent = $agent;
        $this->customName = $agent->name;
        $this->customDescription = $agent->description;
    }

    public function render()
    {
        $workspace = SlackWorkspace::where('agent_id', $this->agent->id)->first();

        return view('livewire.slack.agent-slack-settings', [
            'workspace' => $workspace,
        ]);
    }

    protected function guard(): void
    {
        $user = auth()->user();

        if (!$user || !($user->isAdmin() || $this->agent->canAdmin($user))) {
            abort(403, 'You are not authorized to manage this agent.');
        }
    }

    public function addToSlack(SlackAppProvisioningService $provisioning): void
    {
        $this->guard();

        try {
            $workspace = $provisioning->provision(
                $this->agent,
                $this->customName ?: $this->agent->name,
                $this->customDescription ?: $this->agent->description,
            );

            $authorizeUrl = $provisioning->getAuthorizeUrl($workspace);

            $this->dispatch('open-window', ['url' => $authorizeUrl]);
        } catch (\Exception $e) {
            Log::error('Add to Slack failed', [
                'agent_id' => $this->agent->id,
                'error' => $e->getMessage(),
            ]);

            $this->addError('slack', 'Failed to start Slack provisioning: ' . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->guard();

        $workspace = SlackWorkspace::where('agent_id', $this->agent->id)->first();

        if ($workspace) {
            $workspace->update(['status' => 'disabled']);
        }

        $this->dispatch('refresh');
    }
}
