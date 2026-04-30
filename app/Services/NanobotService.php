<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentExecution;
use App\Models\AgentTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NanobotService
{
    public function generateConfig(Agent $agent, AgentExecution $execution): array
    {
        $template = $agent->template;
        
        $config = [
            'agent_name' => $agent->name,
            'agent_id' => (string) $agent->id,
            'execution_id' => (string) $execution->id,
            'config' => array_merge(
                $template->config_defaults,
                $agent->config_template,
                $agent->agent_config
            ),
            'environment' => $template->env_defaults,
            'tools' => $template->tool_definitions,
            'workspace_path' => '/workspace',
        ];
        
        return $config;
    }
    
    public function generateAgentFile(AgentTemplate $template, Agent $agent, AgentExecution $execution): string
    {
        $content = "### Agent Configuration\n\n";
        $content .= "## Agent Information\n";
        $content .= "Name: {$agent->name}\n";
        $content .= "Description: {$agent->description}\n";
        $content .= "Execution ID: {$execution->id}\n";
        $content .= "Template ID: {$template->id}\n\n";
        
        $content .= "## System Prompt\n\n";
        $content .= "---\n\n";
        $content .= $template->system_prompt;
        $content .= "\n\n---\n\n";
        
        $content .= "## Agent Configuration\n\n";
        $content .= "```yaml\n";
        $config = $this->generateConfig($agent, $execution);
        $content .= yaml_emit($config, YAML_ANY_ENCODING);
        $content .= "\n```\n\n";
        
        if ($template->user_context) {
            $content .= "## User Context\n\n";
            $content .= "---\n\n";
            $content .= $template->user_context;
            $content .= "\n\n---\n\n";
        }
        
        $content .= "## Tools Available\n\n";
        if (!empty($template->tool_definitions)) {
            $content .= "- " . implode("\n- ", $template->tool_definitions);
        } else {
            $content .= "No tools defined";
        }
        
        return $content;
    }
    
    public function generateUserFile(AgentTemplate $template, AgentExecution $execution): string
    {
        $content = "### User Context for Execution\n\n";
        $content .= "Execution ID: {$execution->id}\n";
        $content .= "Timestamp: " . now()->toIso8601String() . "\n\n";
        
        $content .= "## System Instructions\n\n";
        $content .= "Follow the system prompt provided in AGENT.md";
        
        return $content;
    }
    
    public function assembleCommand(
        Agent $agent,
        AgentExecution $execution,
        array $config,
        array $envVariables
    ): array {
        $podConfig = [
            'metadata' => [
                'name' => "agent-pod-{$execution->id}-" . Str::random(8),
                'labels' => [
                    'app' => 'nanobot-agent',
                    'execution-id' => (string) $execution->id,
                    'agent-id' => (string) $agent->id,
                ],
            ],
            'spec' => [
                'volumes' => [
                    [
                        'name' => 'workspace',
                        'persistentVolumeClaim' => [
                            'claimName' => "agent-pvc-{$execution->id}",
                        ],
                    ],
                ],
                'containers' => [
                    [
                        'name' => 'agent',
                        'image' => config('kubernetes.pod_image', 'nanobot-agent:latest'),
                        'imagePullPolicy' => 'IfNotPresent',
                        'volumeMounts' => [
                            [
                                'name' => 'workspace',
                                'mountPath' => '/workspace',
                            ],
                        ],
                        'env' => array_map(function ($key, $value) {
                            return [
                                'name' => $key,
                                'value' => $value,
                            ];
                        }, array_keys($envVariables), $envVariables),
                        'resources' => [
                            'limits' => [
                                'memory' => '512Mi',
                                'cpu' => '500m',
                            ],
                            'requests' => [
                                'memory' => '256Mi',
                                'cpu' => '250m',
                            ],
                        ],
                        'command' => [
                            'nanobot',
                            '--agent-file',
                            '/workspace/AGENT.md',
                            '--user-file',
                            '/workspace/USER.md',
                            '--input-file',
                            '/workspace/input.json',
                        ],
                        'args' => [],
                        'volumeMounts' => [
                            [
                                'name' => 'workspace',
                                'mountPath' => '/workspace',
                            ],
                        ],
                    ],
                ],
                'restartPolicy' => 'OnFailure',
                'activeDeadlineSeconds' => config('kubernetes.timeout_seconds', 3600),
            ],
        ];
        
        return $podConfig;
    }
    
    public function generateToolDefinitions(array $tools): string
    {
        return json_encode([
            'tools' => $tools,
        ], JSON_PRETTY_PRINT);
    }
    
    public function validateConfig(array $config): array
    {
        $errors = [];
        
        if (empty($config['model'])) {
            $errors[] = 'Missing required field: model';
        }
        
        if (isset($config['temperature']) && !is_numeric($config['temperature'])) {
            $errors[] = 'Temperature must be a numeric value';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
