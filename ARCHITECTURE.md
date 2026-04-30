# Agent Desk Services Architecture

## Overview

The Agent Desk application uses a service-oriented architecture to manage agent executions, Kubernetes orchestration, file operations, and notifications.

## Service Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     AgentController                             │
│                    (API Request Handler)                         │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                   AgentOrchestrator                              │
│                  (Orchestration Layer)                           │
│  - Coordinate all services                                      │
│  - Manage execution lifecycle                                   │
│  - Handle errors & cleanup                                      │
└──────────────┬──────────────────────┬──────────────────────────┘
               │                      │
               ▼                      ▼
    ┌─────────────────┐    ┌──────────────────┐
    │  NanobotService │    │   Kubernetes      │
    │  - File gen     │    │  Service          │
    │  - Config gen   │    │  - Pod mgmt       │
    │  - Tool def     │    │  - PVC mgmt       │
    └────────┬────────┘    └────────┬─────────┘
             │                      │
             ▼                      ▼
    ┌──────────────────────────────────────┐
    │      AgentFileService                │
    │  - Workspace file operations         │
    │  - Storage management                 │
    │  - File utilities                    │
    └──────────────────────────────────────┘
```

## Service Details

### 1. AgentOrchestrator

**Location:** `app/Services/AgentOrchestrator.php`

**Purpose:** Coordinates agent execution lifecycle and manages service interactions.

**Key Methods:**

```php
executeAgent($agentId, $input, $context): AgentExecution
```

**Execution Flow:**

1. Create execution record
2. Generate PVC for workspace
3. Generate agent files (AGENT.md, USER.md)
4. Create pod configuration
5. Launch Kubernetes pod
6. Update execution status

**Dependencies:**
- NanobotService
- KubernetesService
- AgentFileService
- NotificationService

### 2. KubernetesService

**Location:** `app/Services/KubernetesService.php`

**Purpose:** Manages Kubernetes pod and persistent volume creation.

**Key Methods:**

```php
createAgentPod($executionId, $agent): array
getPodStatus($podName, $namespace): ?array
getPodLogs($podName, $namespace): string
deletePod($podName, $namespace): bool
watchPods($namespace, callable $callback): void
createPVC($name, $storageClass, $size): string
deletePVC($name, $namespace): bool
```

**Features:**

- **Mock Mode:** Works without Kubernetes integration (for development)
- **Production Mode:** Full Kubernetes API integration
- **Resource Limits:** CPU and memory constraints
- **Timeout Management:** Pod lifecycle management

### 3. NanobotService

**Location:** `app/Services/NanobotService.php`

**Purpose:** Generates configuration files and builds pod commands for Nanobot agents.

**Key Methods:**

```php
generateConfig($agent, $execution): array
generateAgentFile($template, $agent, $execution): string
generateUserFile($template, $execution): string
assembleCommand($agent, $execution, $config, $envVariables): array
validateConfig($config): array
```

**Generated Files:**

- `AGENT.md` - System prompt and agent configuration
- `USER.md` - User context for execution
- `input.json` - Execution input
- `context.json` - Additional context (if provided)
- `config.yaml` - Agent configuration

### 4. AgentFileService

**Location:** `app/Services/AgentFileService.php`

**Purpose:** Manages workspace file operations for agent executions.

**Key Methods:**

```php
saveWorkspaceFile($execution, $filePath, $content): void
readWorkspaceFile($execution, $filePath): ?string
listWorkspaceFiles($execution): array
deleteWorkspaceFile($execution, $filePath): bool
cleanupWorkspace($execution): bool
appendToWorkspaceFile($execution, $filePath, $content): bool
getWorkspaceStats($execution): array
```

**Features:**

- **Storage Abstraction:** Works with Laravel Storage facade
- **File Management:** Read, write, list, and delete files
- **Statistics:** Track file sizes and types
- **Cleanup:** Remove entire workspace directories

### 5. AgentTemplateService

**Location:** `app/Services/AgentTemplateService.php`

**Purpose:** Manages template operations and validation.

**Key Methods:**

```php
getTemplateCategories(): array
getPopularTemplates($limit): array
searchTemplates($query, $filters): array
validateTemplate($template): array
getTemplateStats($template): array
createTemplateFromDescription($description): array
exportTemplateSchema(): array
```

**Features:**

- **Validation:** Check template structure and content
- **Statistics:** Track template usage and success rates
- **Search:** Full-text search with filters
- **Categorization:** Template organization

### 6. NotificationService

**Location:** `app/Services/NotificationService.php`

**Purpose:** Handles notifications and webhook delivery.

**Key Methods:**

```php
sendWebhook($webhook, $data): bool
updateExecutionProgress($execution, $progress, $message): bool
sendExecutionCompleted($execution): bool
sendExecutionFailed($execution, $error): bool
sendExecutionStarted($execution): bool
notifyTemplateCreated($template): bool
formatNotificationMessage($type, $data): string
```

**Features:**

- **Retries:** Automatic retry with exponential backoff
- **Timeouts:** Request timeout management
- **Webhook Integration:** Multiple webhook support
- **Event Types:** Different notification types

## Configuration

### Kubernetes Configuration

Create `config/kubernetes.php`:

```php
<?php

return [
    'enabled' => env('KUBERNETES_ENABLED', false),
    'master_url' => env('KUBERNETES_MASTER_URL', 'https://kubernetes.default.svc'),
    'namespace' => env('KUBERNETES_NAMESPACE', 'agent-desk'),
    'pod_image' => env('AGENT_POD_IMAGE', 'nanobot-agent:latest'),
    'pvc_size' => env('AGENT_PVC_SIZE', '1Gi'),
    'timeout_seconds' => env('AGENT_EXECUTION_TIMEOUT', 3600),
];
```

### Environment Variables

```env
KUBERNETES_ENABLED=true
KUBERNETES_MASTER_URL=https://kubernetes.default.svc
KUBERNETES_NAMESPACE=agent-desk
AGENT_POD_IMAGE=nanobot-agent:latest
AGENT_PVC_SIZE=1Gi
AGENT_EXECUTION_TIMEOUT=3600

# Webhook Configuration (optional)
AGENT_DESK_WEBHOOK_URL=
AGENT_DESK_WEBHOOK_MAX_RETRIES=3
AGENT_DESK_WEBHOOK_RETRY_DELAY=1000
```

## Usage Examples

### Execute an Agent

```php
use App\Services\AgentOrchestrator;

$orchestrator = app(AgentOrchestrator::class);

$execution = $orchestrator->executeAgent(
    agentId: 1,
    input: [
        'prompt' => 'Analyze this data',
        'options' => []
    ],
    context: [
        'previous_messages' => []
    ]
);
```

### Generate Agent Files

```php
use App\Services\NanobotService;

$nanobot = app(NanobotService::class);

$agentFile = $nanobot->generateAgentFile($template, $agent, $execution);

$podConfig = $nanobot->assembleCommand($agent, $execution, $config, $envVariables);
```

### Manage Workspace Files

```php
use App\Services\AgentFileService;

$fileService = app(AgentFileService::class);

// Save file
$fileService->saveWorkspaceFile($execution, 'task.py', 'def task():\n    pass');

// Read file
$content = $fileService->readWorkspaceFile($execution, 'task.py');

// List files
$files = $fileService->listWorkspaceFiles($execution);

// Cleanup
$fileService->cleanupWorkspace($execution);
```

### Send Notifications

```php
use App\Services\NotificationService;

$notification = app(NotificationService::class);

// Progress update
$notification->updateExecutionProgress($execution, 50, 'Processing data...');

// Completion notification
$notification->sendExecutionCompleted($execution);

// Failure notification
$notification->sendExecutionFailed($execution, 'API timeout');
```

## Workflow Examples

### Full Execution Workflow

```php
public function runAgent($agentId, Request $request)
{
    $orchestrator = app(AgentOrchestrator::class);

    try {
        // Orchestrate the execution
        $execution = $orchestrator->executeAgent(
            agentId: $agentId,
            input: $request->input,
            context: $request->context
        );

        return response()->json([
            'message' => 'Execution started',
            'execution' => $execution
        ], 202);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Execution failed',
            'message' => $e->getMessage()
        ], 500);
    }
}
```

### Custom Workflow with Services

```php
public function customTask($agentId, Request $request)
{
    $orchestrator = app(AgentOrchestrator::class);
    $fileService = app(AgentFileService::class);
    $nanobot = app(NanobotService::class);

    // Generate files
    $files = $nanobot->generateAgentFiles($execution, $input);

    // Save to workspace
    foreach ($files as $filename => $content) {
        $fileService->saveWorkspaceFile($execution, $filename, $content);
    }

    // Execute
    $execution = $orchestrator->executeAgent($agentId, $input);

    // Monitor progress
    $this->monitorExecution($execution->id);

    return $execution;
}
```

## Error Handling

All services include comprehensive error handling:

- **Logging:** All operations are logged
- **Exceptions:** Propagated with context
- **Retries:** Automatic retry for webhooks
- **Cleanup:** Automatic cleanup on failure

## Testing

Services can be tested individually:

```php
use App\Services\AgentOrchestrator;
use App\Models\Agent;

class AgentOrchestratorTest extends TestCase
{
    public function testExecuteAgent()
    {
        $orchestrator = app(AgentOrchestrator::class);

        $execution = $orchestrator->executeAgent(
            agentId: 1,
            input: ['test' => 'data'],
            context: null
        );

        $this->assertInstanceOf(AgentExecution::class, $execution);
        $this->assertEquals('pending', $execution->status);
    }
}
```

## Performance Considerations

- **Lazy Loading:** Services are instantiated on demand
- **Memory Management:** File service cleans up resources
- **Queue Integration:** Long-running operations can be queued
- **Caching:** Template service caches query results

## Security Considerations

- **Input Validation:** All services validate inputs
- **File Permissions:** Secure storage operations
- **API Keys:** Never log sensitive configuration
- **Sanitization:** User input is sanitized before use
