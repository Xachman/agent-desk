<?php

namespace App\Jobs;

use App\Models\AgentDeployment;
use App\Models\AgentExecution;
use App\Models\SlackEvent;
use App\Services\SlackKubernetesRunnerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSlackEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1200;
    public int $tries = 2;

    public function __construct(protected SlackEvent $event)
    {
        //
    }

    public function handle(
        SlackKubernetesRunnerService $runner
    ): void {
        $event = $this->event->fresh();

        if (!$event || $event->status !== 'received') {
            return;
        }

        $event->markProcessing();

        try {
            $workspace = $event->workspace;

            if (!$workspace->isActive()) {
                throw new Exception("Slack workspace {$workspace->id} is not active.");
            }

            $agent = $workspace->agent;

            if (!$agent->is_active) {
                throw new Exception("Agent {$agent->id} is not active.");
            }

            $deployment = $this->ensureDeployment($agent);

            $execution = AgentExecution::create([
                'agent_id' => $agent->id,
                'user_id' => $agent->user_id,
                'input' => [
                    'source' => 'slack',
                    'slack_event_id' => $event->id,
                    'text' => $event->text,
                    'channel' => $event->channel_id,
                    'user' => $event->user_id,
                ],
                'context' => $event->payload,
                'status' => 'running',
                'progress' => 0,
                'config_snapshot' => $agent->agent_config,
                'started_at' => now(),
            ]);

            $event->update(['agent_execution_id' => $execution->id]);

            $run = $runner->runForEvent(
                event: $event,
                deployment: $deployment,
                extraEnv: $agent->env_variables ?? [],
                timeout: $this->timeout - 60,
            );

            $result = $runner->waitForJob($run['job_name'], $this->timeout - 60);

            $logs = $runner->getPodLogsForJob($run['job_name']);

            if ($result['status'] === 'succeeded') {
                $execution->update([
                    'status' => 'completed',
                    'progress' => 100,
                    'completed_at' => now(),
                ]);

                $event->markCompleted([
                    'job_status' => $result,
                    'logs_length' => strlen($logs),
                ]);
            } else {
                throw new Exception('Agent job failed: ' . json_encode($result['job_status'] ?? []));
            }

            Log::info('Slack event processed', [
                'event_id' => $event->id,
                'execution_id' => $execution->id,
                'job_name' => $run['job_name'],
            ]);
        } catch (Exception $e) {
            $event->markFailed($e->getMessage());

            if (isset($execution)) {
                $execution->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            Log::error('Processing Slack event failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        } finally {
            if (isset($run['job_name'])) {
                $runner->deleteJob($run['job_name']);
            }
        }
    }

    protected function ensureDeployment(\App\Models\Agent $agent): AgentDeployment
    {
        $deployment = AgentDeployment::where('agent_id', $agent->id)
            ->where('user_id', $agent->user_id)
            ->first();

        if ($deployment) {
            return $deployment;
        }

        return app(AgentDeploymentService::class)->createDeploymentFromAgent($agent);
    }
}
