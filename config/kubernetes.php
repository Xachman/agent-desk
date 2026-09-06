<?php

return [
    'enabled' => env('KUBERNETES_ENABLED', false),
    'kubeconfig' => env('KUBECONFIG'),
    'context' => env('KUBECTL_CONTEXT'),
    'namespace' => env('KUBERNETES_NAMESPACE', 'agent-desk'),
    'domain' => env('KUBERNETES_DOMAIN', 'agent-services.example.com'),
    'pod_image' => env('AGENT_POD_IMAGE', 'nanobot-agent:latest'),
    'pvc_size' => env('AGENT_PVC_SIZE', '1Gi'),
    'timeout_seconds' => env('AGENT_EXECUTION_TIMEOUT', 3600),
];
