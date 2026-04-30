<?php

return [
    'enabled' => env('KUBERNETES_ENABLED', false),
    'master_url' => env('KUBERNETES_MASTER_URL', 'https://kubernetes.default.svc'),
    'namespace' => env('KUBERNETES_NAMESPACE', 'agent-desk'),
    'pod_image' => env('AGENT_POD_IMAGE', 'nanobot-agent:latest'),
    'pvc_size' => env('AGENT_PVC_SIZE', '1Gi'),
    'timeout_seconds' => env('AGENT_EXECUTION_TIMEOUT', 3600),
];
