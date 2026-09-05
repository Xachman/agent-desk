<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentExecution;
use App\Models\AgentTemplate;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgentOrchestrator
{
    protected NanobotService $nanobotService;
    protected KubernetesService $kubernetesService;
    protected AgentFileService $fileService;
    protected NotificationService $notificationService;
    protected int $timeoutSeconds;
    
    public function __construct(
        NanobotService $nanobotService,
        KubernetesService $kubernetesService,
        AgentFileService $fileService,
        NotificationService $notificationService
    ) {
        $this->nanobotService = $nanobotService;
        $this->kubernetesService = $kubernetesService;
        $this->fileService = $fileService;
        $this->notificationService = $notificationService;
        $this->timeoutSeconds = config('kubernetes.timeout_seconds', 3600);
    }
    
    public function executeAgent(int $agentId, array $input, ?array $context = null): AgentExecution
    {
        try {
            Log::info('Starting agent orchestration', [
                'agent_id' => $agentId,
                'execution_id' => null,
                'input' => $input,
            ]);

            $execution = $this->createExecution($agentId, $input, $context);

            Log::info('Execution record created', [
                'execution_id' => $execution->id,
            ]);

            $pvcName = $this->createPVC($execution);

            Log::info('PVC created', [
                'execution_id' => $execution->id,
                'pvc_name' => $pvcName,
            ]);

            $agentFiles = $this->generateAgentFiles($execution, $input, $context);

            Log::info('Agent files generated', [
                'execution_id' => $execution->id,
                'files' => $agentFiles['files'],
            ]);

            $podConfig = $this->createPodConfiguration($execution, $agentFiles, $pvcName);

            $agent = $execution->agent;
            $pod = $this->kubernetesService->createAgentPod($execution->id, $agent);

            $execution->update([
                'status' => 'running',
                'started_at' => now(),
                'progress' => 10,
            ]);

            $this->notificationService->updateExecutionProgress(
                $execution,
                10,
                'Pod launched successfully'
            );

            Log::info('Agent execution orchestrated', [
                'execution_id' => $execution->id,
                'agent_id' => $agentId,
                'pod_name' => $pod['metadata']['name'],
            ]);

            return $execution;

        } catch (\Exception $e) {
            $execution->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Agent orchestration failed', [
                'execution_id' => $execution->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($execution)) {
                $this->notificationService->sendExecutionFailed(
                    $execution,
                    $e->getMessage()
                );
            }

            throw $e;
        }
    }
    
    protected function createExecution(int $agentId, array $input, ?array $context): AgentExecution
    {
        $agent = Agent::with(['user', 'template'])->findOrFail($agentId);

        return AgentExecution::create([
            'agent_id' => $agentId,
            'user_id' => $agent->user_id,
            'input' => $input,
            'context' => $context ?? [],
            'status' => 'pending',
            'progress' => 0,
            'config_snapshot' => $agent->agent_config,
        ]);
    }
    
    protected function generateAgentFiles(AgentExecution $execution, array $input, ?array $context): array
    {
        $agent = $execution->agent;
        $template = $agent->template;

        $agentConfig = array_merge(
            $template->config_defaults,
            $agent->config_template,
            $agent->agent_config
        );

        $envVariables = array_merge(
            $template->env_defaults,
            $agent->env_variables
        );

        $agentFiles = [
            'AGENT.md' => $this->nanobotService->generateAgentFile($template, $agent, $execution),
            'USER.md' => $this->nanobotService->generateUserFile($template, $execution),
            'input.json' => json_encode($input, JSON_PRETTY_PRINT),
        ];

        if ($context) {
            $agentFiles['context.json'] = json_encode($context, JSON_PRETTY_PRINT);
        }

        foreach ($agentFiles as $filename => $content) {
            $this->fileService->saveWorkspaceFile($execution, $filename, $content);
        }

        return [
            'files' => array_keys($agentFiles),
            'env_variables' => $envVariables,
            'config' => $agentConfig,
        ];
    }
    
    protected function createPVC(AgentExecution $execution): string
    {
        $pvcName = "agent-pvc-{$execution->id}-" . Str::random(8);
        
        $this->kubernetesService->createPVC(
            $pvcName,
            'ssd',
            config('kubernetes.pvc_size', '1Gi')
        );
        
        return $pvcName;
    }
    
    protected function createPodConfiguration(AgentExecution $execution, array $agentFiles, string $pvcName): array
    {
        return $this->nanobotService->assembleCommand(
            $execution->agent,
            $execution,
            $agentFiles['config'],
            $agentFiles['env_variables']
        );
    }
}
