<?php

namespace App\Services;

use App\Models\AgentDeployment;
use App\Models\SlackEvent;
use App\Models\SlackWorkspace;
use Exception;
use Illuminate\Support\Facades\Log;

class SlackKubernetesRunnerService
{
    protected AgentDeploymentService $deploymentService;
    protected KubernetesService $kubernetes;

    public function __construct(
        AgentDeploymentService $deploymentService,
        KubernetesService $kubernetes
    ) {
        $this->deploymentService = $deploymentService;
        $this->kubernetes = $kubernetes;
    }

    public function runForEvent(SlackEvent $event, AgentDeployment $deployment, array $extraEnv = [], int $timeout = 600): array
    {
        $workspace = $event->workspace;
        $jobName = $this->jobName($workspace, $event);
        $image = $deployment->image ?: config('kubernetes.pod_image', 'nousresearch/hermes-agent:v2026.4.30');
        $dataPvcName = "{$workspace->storage_slug}-data-pvc";
        $filesystemDbPvcName = "{$workspace->storage_slug}-filesystem-db-pvc";

        $this->ensurePvcs($workspace, $deployment);
        app(SlackWorkspaceSecretService::class)->apply($workspace);

        $manifest = $this->deploymentService->jobManifest(
            jobName: $jobName,
            image: $image,
            workspace: $workspace,
            deployment: $deployment,
            command: $this->buildCommand($event),
            env: $this->buildEnv($workspace, $event, $extraEnv),
            dataPvcName: $dataPvcName,
            filesystemDbPvcName: $filesystemDbPvcName,
            timeout: $timeout,
        );

        $yaml = $this->deploymentService->toYaml([$manifest]);

        $this->kubernetes->applyManifest($yaml);

        Log::info('Slack agent job started', [
            'event_id' => $event->id,
            'job_name' => $jobName,
        ]);

        return [
            'job_name' => $jobName,
            'yaml' => $yaml,
        ];
    }

    public function waitForJob(string $jobName, int $timeoutSeconds = 600): array
    {
        $interval = 2;
        $elapsed = 0;

        while ($elapsed < $timeoutSeconds) {
            $status = $this->kubernetes->getJobStatus($jobName);
            $succeeded = (int) data_get($status, 'status.succeeded', 0);
            $failed = (int) data_get($status, 'status.failed', 0);
            $completionTime = data_get($status, 'status.completionTime');

            if ($succeeded > 0 || $completionTime) {
                return ['status' => 'succeeded', 'job_status' => $status];
            }

            if ($failed > 0) {
                return ['status' => 'failed', 'job_status' => $status];
            }

            sleep($interval);
            $elapsed += $interval;
        }

        throw new Exception("Timeout waiting for job {$jobName} after {$timeoutSeconds}s");
    }

    public function getPodLogsForJob(string $jobName, ?string $container = 'agent'): string
    {
        $pods = $this->kubernetes->getPods("job-name={$jobName}");

        if (empty($pods)) {
            return '';
        }

        $podName = $pods[0]['metadata']['name'] ?? null;

        if (!$podName) {
            return '';
        }

        return $this->kubernetes->getPodLogs($podName, $container);
    }

    public function deleteJob(string $jobName): void
    {
        try {
            $this->kubernetes->deleteResource('job', $jobName);
        } catch (Exception $e) {
            Log::warning('Failed to delete Slack agent job', [
                'job_name' => $jobName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function ensurePvcs(SlackWorkspace $workspace, AgentDeployment $deployment): void
    {
        $size = config('kubernetes.slack_pvc_size', '2Gi');
        $dataPvcName = "{$workspace->storage_slug}-data-pvc";
        $filesystemDbPvcName = "{$workspace->storage_slug}-filesystem-db-pvc";

        $yaml = $this->deploymentService->toYaml([
            [
                'apiVersion' => 'v1',
                'kind' => 'PersistentVolumeClaim',
                'metadata' => ['name' => $dataPvcName],
                'spec' => [
                    'accessModes' => ['ReadWriteOnce'],
                    'resources' => ['requests' => ['storage' => $size]],
                ],
            ],
            [
                'apiVersion' => 'v1',
                'kind' => 'PersistentVolumeClaim',
                'metadata' => ['name' => $filesystemDbPvcName],
                'spec' => [
                    'accessModes' => ['ReadWriteOnce'],
                    'resources' => ['requests' => ['storage' => $size]],
                ],
            ],
        ]);

        $this->kubernetes->applyManifest($yaml);
    }

    protected function buildCommand(SlackEvent $event): array
    {
        $text = $event->text ?? '';
        $sessionId = $event->workspace->storage_slug;
        $payload = base64_encode(json_encode($event->payload));

        return [
            'sh', '-c',
            "echo '{$payload}' | base64 -d > /tmp/slack-event.json && hermes chat --pass-session-id '{$sessionId}' --message " . escapeshellarg($text),
        ];
    }

    protected function buildEnv(SlackWorkspace $workspace, SlackEvent $event, array $extraEnv): array
    {
        $baseEnv = [
            ['name' => 'SLACK_BOT_TOKEN', 'valueFrom' => ['secretKeyRef' => ['name' => "{$workspace->storage_slug}-secrets", 'key' => 'slack-bot-token']]],
            ['name' => 'SLACK_WORKSPACE_ID', 'value' => $workspace->id],
            ['name' => 'SLACK_EVENT_ID', 'value' => $event->id],
            ['name' => 'SLACK_API_BASE', 'value' => config('services.slack.api_base', 'https://slack.com/api')],
            ['name' => 'AGENT_DESK_WEBHOOK_URL', 'value' => config('services.slack.oauth.agent_callback_url')],
            ['name' => 'DOCKER_HOST', 'value' => 'tcp://localhost:2375'],
        ];

        foreach ($extraEnv as $key => $value) {
            $baseEnv[] = ['name' => $key, 'value' => (string) $value];
        }

        return $baseEnv;
    }

    protected function jobName(SlackWorkspace $workspace, SlackEvent $event): string
    {
        return "{$workspace->storage_slug}-event-" . substr($event->id, 0, 8);
    }
}
