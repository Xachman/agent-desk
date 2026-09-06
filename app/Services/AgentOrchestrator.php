<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentDeployment;
use App\Models\AgentExecution;
use App\Models\AgentSecret;
use Illuminate\Support\Facades\Log;

class AgentOrchestrator
{
    protected AgentDeploymentService $deploymentService;
    protected KubernetesService $kubernetesService;
    protected NotificationService $notificationService;

    public function __construct(
        AgentDeploymentService $deploymentService,
        KubernetesService $kubernetesService,
        NotificationService $notificationService
    ) {
        $this->deploymentService = $deploymentService;
        $this->kubernetesService = $kubernetesService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute an agent by finding or creating its Kubernetes deployment,
     * then triggering a run inside the cluster.
     */
    public function executeAgent(int $agentId, array $input, ?array $context = null): AgentExecution
    {
        $agent = Agent::with('user')->findOrFail($agentId);

        $execution = AgentExecution::create([
            'agent_id' => $agentId,
            'user_id' => $agent->user_id,
            'input' => $input,
            'context' => $context ?? [],
            'status' => 'pending',
            'progress' => 0,
            'config_snapshot' => $agent->agent_config,
        ]);

        try {
            $deployment = $this->ensureDeploymentForAgent($agent);

            // Scale up if not running
            if ($deployment->replicas === 0) {
                $this->deploymentService->scale($deployment, 1);
            }

            // Trigger a job inside the pod via kubectl exec
            $this->triggerAgentRun($deployment, $execution, $input, $context);

            $execution->update([
                'status' => 'running',
                'started_at' => now(),
                'progress' => 10,
            ]);

            $this->notificationService->updateExecutionProgress(
                $execution,
                10,
                'Agent deployment is running'
            );

            Log::info('Agent execution started via Kubernetes deployment', [
                'execution_id' => $execution->id,
                'agent_id' => $agentId,
                'deployment_id' => $deployment->id,
            ]);

            return $execution;
        } catch (\Exception $e) {
            $execution->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Agent orchestration failed', [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->notificationService->sendExecutionFailed($execution, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Find or create a Kubernetes deployment for the agent.
     */
    protected function ensureDeploymentForAgent(Agent $agent): AgentDeployment
    {
        $deployment = AgentDeployment::where('agent_id', $agent->id)
            ->where('user_id', $agent->user_id)
            ->first();

        if ($deployment) {
            return $deployment;
        }

        $deployment = AgentDeployment::create([
            'user_id' => $agent->user_id,
            'agent_id' => $agent->id,
            'name' => $agent->name . ' Deployment',
            'slug' => \Illuminate\Support\Str::slug($agent->name . '-' . substr($agent->id, 0, 8)),
            'description' => 'Auto-created deployment for agent ' . $agent->name,
            'image' => 'nousresearch/hermes-agent:v2026.4.30',
            'namespace' => config('kubernetes.namespace', 'agent-desk'),
            'domain' => config('kubernetes.domain', 'agent-services.example.com'),
            'replicas' => 0,
            'status' => 'pending',
            'is_active' => true,
            'model_config' => [
                'default' => 'kimi-k2.6:cloud',
                'provider' => 'ollama-cloud',
                'api_mode' => 'chat_completions',
            ],
            'soul_markdown' => null,
            'agents_markdown' => null,
            'config_yaml' => null,
            'env_variables' => $agent->env_variables,
            'secrets' => [],
            'resource_limits' => [
                'agent' => [
                    'limits' => ['memory' => '8000Mi', 'cpu' => '2000m'],
                    'requests' => ['memory' => '500Mi', 'cpu' => '500m'],
                ],
                'dind' => [
                    'limits' => ['memory' => '4000Mi', 'cpu' => '2000m'],
                    'requests' => ['memory' => '1000Mi', 'cpu' => '500m'],
                ],
            ],
        ]);

        $this->deploymentService->deploy($deployment);

        return $deployment;
    }

    /**
     * Trigger a run inside the agent pod by creating a marker file.
     * In a real implementation this could enqueue a job or send a message.
     */
    protected function triggerAgentRun(AgentDeployment $deployment, AgentExecution $execution, array $input, ?array $context): void
    {
        $pods = $this->kubernetesService->getPods('app=agent');

        if (empty($pods)) {
            throw new \Exception('No agent pods are running. Deployment may still be starting.');
        }

        $pod = $pods[0];
        $podName = $pod['metadata']['name'];

        $runPayload = json_encode([
            'execution_id' => $execution->id,
            'input' => $input,
            'context' => $context,
            'created_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT);

        // Write the run payload into the pod's workspace
        $this->kubernetesService->kubectl([
            'exec', $podName, '-c', 'agent', '--',
            'bash', '-c', 'cat > /opt/data/execution.json',
        ], $runPayload);

        // Optionally trigger a command inside the pod
        $this->kubernetesService->kubectl([
            'exec', $podName, '-c', 'agent', '--',
            'touch', '/opt/data/run.trigger',
        ]);

        Log::info('Agent run triggered in pod', [
            'execution_id' => $execution->id,
            'pod_name' => $podName,
        ]);
    }
}
