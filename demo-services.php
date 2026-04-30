#!/usr/bin/env php
<?php

use App\Models\Agent;
use App\Models\AgentTemplate;
use App\Services\AgentOrchestrator;
use App\Services\KubernetesService;
use App\Services\NanobotService;
use App\Services\AgentFileService;
use App\Services\AgentTemplateService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Agent Desk Services Demo\n";
echo "=======================\n\n";

try {
    DB::beginTransaction();

    // 1. Create test templates
    echo "1. Creating test templates...\n";

    $template1 = AgentTemplate::create([
        'name' => 'Code Reviewer',
        'description' => 'Reviews code for best practices and bugs',
        'system_prompt' => 'You are a code reviewer. Analyze code for best practices, security issues, and bugs.',
        'user_context' => null,
        'config_defaults' => [
            'model' => 'gpt-4',
            'temperature' => 0.7,
        ],
        'env_defaults' => [],
        'tool_definitions' => [
            [
                'name' => 'code_search',
                'description' => 'Search codebase',
            ],
        ],
    ]);

    $template2 = AgentTemplate::create([
        'name' => 'Data Analyst',
        'description' => 'Analyze and visualize data',
        'system_prompt' => 'You are a data analyst. Provide insights and visualizations.',
        'user_context' => null,
        'config_defaults' => [
            'model' => 'gpt-4',
            'temperature' => 0.5,
        ],
        'env_defaults' => [],
        'tool_definitions' => [],
    ]);

    echo "   - Created: {$template1->name}\n";
    echo "   - Created: {$template2->name}\n\n";

    // 2. Create test agents
    echo "2. Creating test agents...\n";

    $agent1 = Agent::create([
        'user_id' => 1,
        'template_id' => $template1->id,
        'name' => 'My Code Reviewer',
        'description' => 'Custom code reviewer',
        'agent_config' => [
            'review_depth' => 'thorough',
        ],
        'env_variables' => [],
        'config_template' => [],
        'is_active' => true,
    ]);

    $agent2 = Agent::create([
        'user_id' => 1,
        'template_id' => $template2->id,
        'name' => 'My Data Analyst',
        'description' => 'Custom data analyst',
        'agent_config' => [],
        'env_variables' => [],
        'config_template' => [],
        'is_active' => true,
    ]);

    echo "   - Created: {$agent1->name}\n";
    echo "   - Created: {$agent2->name}\n\n";

    // 3. Initialize services
    echo "3. Initializing services...\n";

    $orchestrator = app(AgentOrchestrator::class);
    $kubernetes = app(KubernetesService::class);
    $nanobot = app(NanobotService::class);
    $fileService = app(AgentFileService::class);
    $templateService = app(AgentTemplateService::class);
    $notificationService = app(NotificationService::class);

    echo "   - AgentOrchestrator: Ready\n";
    echo "   - KubernetesService: Ready\n";
    echo "   - NanobotService: Ready\n";
    echo "   - AgentFileService: Ready\n";
    echo "   - AgentTemplateService: Ready\n";
    echo "   - NotificationService: Ready\n\n";

    // 4. Demonstrate template service
    echo "4. Template Service Demo:\n";
    echo "   - Categories: " . implode(', ', $templateService->getTemplateCategories()) . "\n";
    $popular = $templateService->getPopularTemplates(1);
    echo "   - Popular templates: " . ($popular[0]->name ?? 'N/A') . "\n";
    echo "   - Validation: " . ($templateService->validateTemplate($template1)['valid'] ? 'Valid' : 'Invalid') . "\n\n";

    // 5. Demonstrate nanobot service
    echo "5. Nanobot Service Demo:\n";

    $execution = AgentExecution::create([
        'agent_id' => $agent1->id,
        'user_id' => 1,
        'input' => ['task' => 'Review this code'],
        'context' => [],
        'status' => 'pending',
        'progress' => 0,
    ]);

    $config = $nanobot->generateConfig($agent1, $execution);
    echo "   - Generated config for execution\n";

    $agentFile = $nanobot->generateAgentFile($template1, $agent1, $execution);
    echo "   - Generated AGENT.md\n";

    $userFile = $nanobot->generateUserFile($template1, $execution);
    echo "   - Generated USER.md\n";

    $podConfig = $nanobot->assembleCommand($agent1, $execution, $config['config'], $config['environment']);
    echo "   - Assembled pod command\n\n";

    // 6. Demonstrate file service
    echo "6. Agent File Service Demo:\n";

    $fileService->saveWorkspaceFile($execution, 'input.json', json_encode(['task' => 'Review this code']));

    $content = $fileService->readWorkspaceFile($execution, 'input.json');
    echo "   - Saved and read file: input.json\n";

    $files = $fileService->listWorkspaceFiles($execution);
    echo "   - Listed files: " . count($files) . " files\n";

    $stats = $fileService->getWorkspaceStats($execution);
    echo "   - Workspace stats: {$stats['file_count']} files, {$stats['total_size_formatted']}\n\n";

    // 7. Demonstrate kubernetes service
    echo "7. Kubernetes Service Demo:\n";

    $podName = "demo-pod-" . time();
    $mockConfig = [
        'metadata' => ['name' => $podName],
        'spec' => [],
    ];

    $createdPod = $kubernetes->createAgentPod($execution->id, $agent1);
    echo "   - Created pod: {$createdPod['metadata']['name']}\n";

    $status = $kubernetes->getPodStatus($createdPod['metadata']['name']);
    echo "   - Pod status: " . $status['status']['phase'] . "\n";

    $logs = $kubernetes->getPodLogs($createdPod['metadata']['name']);
    echo "   - Retrieved logs (" . strlen($logs) . " bytes)\n";

    $deleted = $kubernetes->deletePod($createdPod['metadata']['name']);
    echo "   - Deleted pod: " . ($deleted ? 'Yes' : 'No') . "\n\n";

    // 8. Demonstrate notification service
    echo "8. Notification Service Demo:\n";

    $notificationService->updateExecutionProgress($execution, 25, 'Initializing...');
    echo "   - Progress update: 25%\n";

    $notificationService->updateExecutionProgress($execution, 50, 'Processing...');
    echo "   - Progress update: 50%\n";

    $notificationService->updateExecutionProgress($execution, 75, 'Finalizing...');
    echo "   - Progress update: 75%\n\n";

    DB::commit();

    echo "✓ All services demo completed successfully!\n\n";

    echo "Services Summary:\n";
    echo "  - AgentOrchestrator: Handles execution lifecycle\n";
    echo "  - KubernetesService: Manages Kubernetes resources\n";
    echo "  - NanobotService: Generates agent configurations\n";
    echo "  - AgentFileService: Manages workspace files\n";
    echo "  - AgentTemplateService: Template management\n";
    echo "  - NotificationService: Webhook delivery\n";

} catch (\Exception $e) {
    DB::rollBack();

    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";
