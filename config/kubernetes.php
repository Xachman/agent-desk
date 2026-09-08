<?php

return [
    'enabled' => env('KUBERNETES_ENABLED', false),
    'kubeconfig' => env('KUBECONFIG'),
    'context' => env('KUBECTL_CONTEXT'),
    'namespace' => env('KUBERNETES_NAMESPACE', 'agent-desk'),
    'domain' => env('KUBERNETES_DOMAIN', 'agent-services.example.com'),
    'pod_image' => env('AGENT_POD_IMAGE', 'nanobot-agent:latest'),
    'pvc_size' => env('AGENT_PVC_SIZE', '1Gi'),
    'slack_pvc_size' => env('SLACK_PVC_SIZE', '2Gi'),
    'timeout_seconds' => env('AGENT_EXECUTION_TIMEOUT', 3600),
    'job_ttl_seconds' => env('AGENT_JOB_TTL_SECONDS', 600),
    'job_backoff_limit' => env('AGENT_JOB_BACKOFF_LIMIT', 1),
    'job_active_deadline_seconds' => env('AGENT_JOB_ACTIVE_DEADLINE_SECONDS', 3600),
];
