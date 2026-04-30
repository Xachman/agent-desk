<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KubernetesService
{
    protected int $timeoutSeconds;
    
    public function __construct()
    {
        $this->timeoutSeconds = config('kubernetes.timeout_seconds', 3600);
    }
    
    public function createAgentPod(int $executionId, Agent $agent): array
    {
        if (!config('kubernetes.enabled', false)) {
            return $this->createMockPod($executionId, $agent);
        }

        $namespace = config('kubernetes.namespace', 'agent-desk');
        $podConfig = $this->generatePodConfig($executionId, $agent);
        
        try {
            $pod = $this->createPod($podConfig, $namespace);
            
            Log::info('Kubernetes pod created', [
                'execution_id' => $executionId,
                'pod_name' => $pod['metadata']['name'],
            ]);
            
            return $pod;
            
        } catch (\Exception $e) {
            Log::error('Failed to create Kubernetes pod', [
                'execution_id' => $executionId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function getPodStatus(string $podName, string $namespace): ?array
    {
        if (!config('kubernetes.enabled', false)) {
            return $this->getMockPodStatus($podName);
        }

        try {
            $response = $this->kubernetes->getPod($podName, $namespace);
            
            if ($response['items']) {
                return $response['items'][0];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Failed to get pod status', [
                'pod_name' => $podName,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function getPodLogs(string $podName, string $namespace, array $options = []): string
    {
        if (!config('kubernetes.enabled', false)) {
            return $this->getMockLogs($podName);
        }

        try {
            $response = $this->kubernetes->getPodLogs($podName, $namespace, $options);
            
            Log::debug('Retrieved pod logs', [
                'pod_name' => $podName,
                'log_length' => strlen($response),
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Failed to get pod logs', [
                'pod_name' => $podName,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function deletePod(string $podName, string $namespace): bool
    {
        if (!config('kubernetes.enabled', false)) {
            return true;
        }

        try {
            $this->kubernetes->deletePod($podName, $namespace);
            
            Log::info('Kubernetes pod deleted', [
                'pod_name' => $podName,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to delete Kubernetes pod', [
                'pod_name' => $podName,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function watchPods(string $namespace, callable $callback, int $timeout = 300): void
    {
        if (!config('kubernetes.enabled', false)) {
            $this->mockWatchPods($namespace, $callback);
            return;
        }

        try {
            $this->kubernetes->watchPods($namespace, $callback, $timeout);
            
            Log::info('Pod watching started', [
                'namespace' => $namespace,
                'timeout' => $timeout,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to watch pods', [
                'namespace' => $namespace,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function createPVC(string $name, string $storageClass, string $size): string
    {
        if (!config('kubernetes.enabled', false)) {
            return "mock-pvc-{$name}";
        }

        try {
            $this->kubernetes->createPVC($name, $storageClass, $size);
            
            Log::info('PVC created', [
                'name' => $name,
                'storage_class' => $storageClass,
                'size' => $size,
            ]);
            
            return $name;
            
        } catch (\Exception $e) {
            Log::error('Failed to create PVC', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function deletePVC(string $name, string $namespace): bool
    {
        if (!config('kubernetes.enabled', false)) {
            return true;
        }

        try {
            $this->kubernetes->deletePVC($name, $namespace);
            
            Log::info('PVC deleted', [
                'name' => $name,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to delete PVC', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    protected function generatePodConfig(int $executionId, Agent $agent): array
    {
        return [
            'metadata' => [
                'name' => "agent-pod-{$executionId}-" . Str::random(8),
                'labels' => [
                    'app' => 'nanobot-agent',
                    'execution-id' => $executionId,
                    'agent-id' => $agent->id,
                ],
            ],
            'spec' => [
                'volumes' => [
                    [
                        'name' => 'workspace',
                        'persistentVolumeClaim' => [
                            'claimName' => "agent-pvc-{$executionId}",
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
                        'env' => [],
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
                        'command' => [],
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
                'activeDeadlineSeconds' => $this->timeoutSeconds,
                'volumes' => [
                    [
                        'name' => 'workspace',
                        'persistentVolumeClaim' => [
                            'claimName' => "agent-pvc-{$executionId}",
                        ],
                    ],
                ],
            ],
        ];
    }
    
    protected function createPod(array $config, string $namespace): array
    {
        if (!class_exists('mk-alex7\Kubernetes\Client')) {
            return $this->createMockPod($config['metadata']['name'], null);
        }

        $client = new \mk-alex7\Kubernetes\Client([
            'masterUrl' => config('kubernetes.master_url'),
            'namespace' => $namespace,
        ]);

        $result = $client->createPod($config);

        return $result ?? $config;
    }
    
    protected function createMockPod(string $podName, ?Agent $agent): array
    {
        return [
            'metadata' => [
                'name' => $podName,
                'namespace' => config('kubernetes.namespace', 'agent-desk'),
                'labels' => [
                    'app' => 'nanobot-agent',
                ],
                'creationTimestamp' => now()->toAtomString(),
            ],
            'spec' => [
                'volumes' => [],
                'containers' => [
                    [
                        'name' => 'agent',
                        'image' => config('kubernetes.pod_image', 'nanobot-agent:latest'),
                        'resources' => [],
                    ],
                ],
                'restartPolicy' => 'OnFailure',
            ],
            'status' => [
                'phase' => 'Pending',
                'conditions' => [],
            ],
        ];
    }
    
    protected function getMockPodStatus(string $podName): array
    {
        return [
            'metadata' => [
                'name' => $podName,
                'namespace' => config('kubernetes.namespace', 'agent-desk'),
            ],
            'spec' => [],
            'status' => [
                'phase' => 'Pending',
                'conditions' => [],
            ],
        ];
    }
    
    protected function getMockLogs(string $podName): string
    {
        return "[MOCK] Pod {$podName} logs\n[MOCK] Execution started at " . now()->toIso8601String();
    }
    
    protected function mockWatchPods(string $namespace, callable $callback, int $timeout): void
    {
        $startTime = time();
        
        while (time() - $startTime < $timeout) {
            $podStatus = $this->getMockPodStatus("mock-pod-" . Str::random(8));
            $callback($podStatus);
            
            sleep(1);
        }
    }
}
