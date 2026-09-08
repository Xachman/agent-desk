<?php

namespace App\Services;

use App\Models\SlackWorkspace;
use Illuminate\Support\Facades\Log;

class SlackWorkspaceSecretService
{
    protected KubernetesService $kubernetes;
    protected AgentDeploymentService $deploymentService;

    public function __construct(
        KubernetesService $kubernetes,
        AgentDeploymentService $deploymentService
    ) {
        $this->kubernetes = $kubernetes;
        $this->deploymentService = $deploymentService;
    }

    public function apply(SlackWorkspace $workspace): void
    {
        $secretName = "{$workspace->storage_slug}-secrets";

        $yaml = $this->deploymentService->toYaml([
            [
                'apiVersion' => 'v1',
                'kind' => 'Secret',
                'metadata' => [
                    'name' => $secretName,
                    'labels' => ['app' => 'agent-desk-slack', 'workspace' => $workspace->storage_slug],
                ],
                'type' => 'Opaque',
                'stringData' => [
                    'slack-bot-token' => $workspace->slack_bot_token ?: '',
                    'slack-workspace-id' => $workspace->id,
                    'slack-team-id' => $workspace->slack_team_id ?: '',
                ],
            ],
        ]);

        $this->kubernetes->applyManifest($yaml);

        Log::info('Slack workspace secret applied', [
            'workspace_id' => $workspace->id,
            'secret_name' => $secretName,
        ]);
    }
}
