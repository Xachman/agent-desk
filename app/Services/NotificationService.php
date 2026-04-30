<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    protected string $webhookUrl;
    protected int $maxRetries;
    protected int $retryDelay;
    
    public function __construct()
    {
        $webhookConfig = config('agent_desk', []);
        $this->webhookUrl = $webhookConfig['webhook_url'] ?? '';
        $this->maxRetries = (int) ($webhookConfig['webhook_max_retries'] ?? 3);
        $this->retryDelay = (int) ($webhookConfig['webhook_retry_delay'] ?? 1000);
    }
    
    public function sendWebhook(string $webhook, array $data): bool
    {
        $url = $webhook;
        
        try {
            for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
                try {
                    $response = Http::timeout(10)->post($url, $data);
                    
                    if ($response->successful()) {
                        Log::info('Webhook sent successfully', [
                            'webhook' => $webhook,
                            'attempt' => $attempt,
                        ]);
                        
                        return true;
                    } else {
                        if ($attempt < $this->maxRetries) {
                            Log::warning('Webhook retry', [
                                'webhook' => $webhook,
                                'attempt' => $attempt,
                                'status' => $response->status(),
                                'delay' => $this->retryDelay,
                            ]);
                            
                            usleep($this->retryDelay * 1000);
                        } else {
                            Log::error('Webhook failed after max retries', [
                                'webhook' => $webhook,
                                'attempt' => $attempt,
                                'status' => $response->status(),
                                'response' => $response->body(),
                            ]);
                        }
                    }
                    
                } catch (\Exception $e) {
                    if ($attempt < $this->maxRetries) {
                        Log::warning('Webhook retry exception', [
                            'webhook' => $webhook,
                            'attempt' => $attempt,
                            'error' => $e->getMessage(),
                            'delay' => $this->retryDelay,
                        ]);
                        
                        usleep($this->retryDelay * 1000);
                    } else {
                        Log::error('Webhook failed after max retries', [
                            'webhook' => $webhook,
                            'attempt' => $attempt,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('Webhook service error', [
                'webhook' => $webhook,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function updateExecutionProgress(
        $execution,
        int $progress,
        string $message = ''
    ): bool {
        try {
            $data = [
                'execution_id' => (string) $execution->id,
                'progress' => $progress,
                'message' => $message,
                'timestamp' => now()->toIso8601String(),
                'status' => $execution->status,
            ];
            
            $webhooks = $execution->user->subscriptions()->where('type', 'progress')->get();
            
            foreach ($webhooks as $webhook) {
                if ($webhook->url) {
                    $this->sendWebhook($webhook->url, $data);
                }
            }
            
            Log::debug('Execution progress updated', [
                'execution_id' => $execution->id,
                'progress' => $progress,
                'message' => $message,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to update execution progress', [
                'execution_id' => $execution->id,
                'progress' => $progress,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function sendExecutionCompleted($execution): bool
    {
        try {
            $data = [
                'execution_id' => (string) $execution->id,
                'status' => 'completed',
                'progress' => 100,
                'message' => 'Execution completed successfully',
                'timestamp' => now()->toIso8601String(),
                'completed_at' => $execution->completed_at?->toIso8601String(),
            ];
            
            $webhooks = $execution->user->subscriptions()->where('type', 'execution_complete')->get();
            
            foreach ($webhooks as $webhook) {
                if ($webhook->url) {
                    $this->sendWebhook($webhook->url, $data);
                }
            }
            
            Log::info('Execution completion notification sent', [
                'execution_id' => $execution->id,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send execution completion notification', [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function sendExecutionFailed($execution, string $error): bool
    {
        try {
            $data = [
                'execution_id' => (string) $execution->id,
                'status' => 'failed',
                'progress' => $execution->progress,
                'message' => $error,
                'timestamp' => now()->toIso8601String(),
                'error' => $error,
                'completed_at' => $execution->completed_at?->toIso8601String(),
            ];
            
            $webhooks = $execution->user->subscriptions()->where('type', 'execution_failed')->get();
            
            foreach ($webhooks as $webhook) {
                if ($webhook->url) {
                    $this->sendWebhook($webhook->url, $data);
                }
            }
            
            Log::error('Execution failure notification sent', [
                'execution_id' => $execution->id,
                'error' => $error,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send execution failure notification', [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function sendExecutionStarted($execution): bool
    {
        try {
            $data = [
                'execution_id' => (string) $execution->id,
                'status' => 'running',
                'progress' => 10,
                'message' => 'Execution started',
                'timestamp' => now()->toIso8601String(),
                'started_at' => $execution->started_at?->toIso8601String(),
            ];
            
            $webhooks = $execution->user->subscriptions()->where('type', 'execution_started')->get();
            
            foreach ($webhooks as $webhook) {
                if ($webhook->url) {
                    $this->sendWebhook($webhook->url, $data);
                }
            }
            
            Log::info('Execution start notification sent', [
                'execution_id' => $execution->id,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send execution start notification', [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function notifyTemplateCreated(AgentTemplate $template): bool
    {
        try {
            $data = [
                'event' => 'template.created',
                'template_id' => (string) $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'timestamp' => now()->toIso8601String(),
            ];
            
            $webhooks = AgentTemplate::whereHas('agents')->get()
                ->pluck('user_id')
                ->unique()
                ->map(function ($userId) {
                    return [
                        'user_id' => $userId,
                        'type' => 'template_created',
                        'url' => '',
                    ];
                })
                ->filter(function ($webhook) {
                    return !empty($webhook['url']);
                });
            
            foreach ($webhooks as $webhook) {
                if ($webhook->url) {
                    $this->sendWebhook($webhook->url, $data);
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to notify template created', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function formatNotificationMessage(
        string $type,
        array $data
    ): string {
        $messages = [
            'progress' => "Progress: {$data['progress']}% - {$data['message']}",
            'completed' => "Execution completed: {$data['execution_id']}",
            'failed' => "Execution failed: {$data['execution_id']} - Error: {$data['error']}",
            'started' => "Execution started: {$data['execution_id']}",
            'template_created' => "New template created: {$data['name']}",
        ];
        
        return $messages[$type] ?? 'Notification';
    }
}
