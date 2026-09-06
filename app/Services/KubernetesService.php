<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentDeployment;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ProcessUtils;
use Symfony\Component\Process\Process;

class KubernetesService
{
    protected int $timeoutSeconds;
    protected ?string $kubeconfig;
    protected ?string $context;
    protected string $namespace;

    public function __construct()
    {
        $this->timeoutSeconds = config('kubernetes.timeout_seconds', 3600);
        $this->kubeconfig = config('kubernetes.kubeconfig');
        $this->context = config('kubernetes.context');
        $this->namespace = config('kubernetes.namespace', 'agent-desk');
    }

    /**
     * Run a kubectl command and return the output.
     */
    public function kubectl(array $args, ?string $input = null, int $timeout = 60): array
    {
        $cmd = ['kubectl'];

        $home = $this->kubectlHome();
        $kubeconfig = $this->prepareKubeconfig($home);

        $cmd[] = '--kubeconfig=' . $kubeconfig;

        if ($this->context) {
            $cmd[] = '--context=' . $this->context;
        }

        $cmd[] = '--namespace=' . $this->namespace;
        $cmd = array_merge($cmd, $args);

        $env = [
            'HOME' => $home,
            'KUBECONFIG' => $kubeconfig,
            'KUBECACHEDIR' => $home . '/.kube/cache',
        ];

        $process = new Process($cmd, base_path(), $env, $input, $timeout);
        $process->run();

        $output = $process->getOutput();
        $error = $process->getErrorOutput();
        $exitCode = $process->getExitCode();

        if ($exitCode !== 0) {
            Log::error('kubectl command failed', [
                'command' => implode(' ', $cmd),
                'error' => $error,
                'output' => $output,
                'env' => $env,
            ]);

            throw new Exception("kubectl failed: {$error}");
        }

        return [
            'output' => $output,
            'error' => $error,
            'exit_code' => $exitCode,
        ];
    }

    /**
     * Get a dedicated home directory for kubectl outside the web root.
     */
    protected function kubectlHome(): string
    {
        $home = storage_path('app/kubectl-home');

        if (!is_dir($home)) {
            mkdir($home, 0700, true);
        }

        return $home;
    }

    /**
     * Apply a multi-document YAML manifest to the cluster.
     */
    public function applyManifest(string $yaml): array
    {
        $this->ensureNamespace();

        $result = $this->kubectl(['apply', '--validate=false', '-f', '-'], $yaml);

        Log::info('Kubernetes manifest applied', [
            'namespace' => $this->namespace,
            'output' => $result['output'],
        ]);

        return $result;
    }

    /**
     * Delete resources defined in a manifest from the cluster.
     */
    public function deleteManifest(string $yaml): array
    {
        $result = $this->kubectl(['delete', '-f', '-', '--ignore-not-found=true'], $yaml);

        Log::info('Kubernetes manifest deleted', [
            'namespace' => $this->namespace,
            'output' => $result['output'],
        ]);

        return $result;
    }

    /**
     * Scale a Deployment to the requested number of replicas.
     */
    public function scaleDeployment(string $name, int $replicas): array
    {
        $result = $this->kubectl(['scale', 'deployment', $name, '--replicas=' . $replicas]);

        Log::info('Kubernetes deployment scaled', [
            'deployment' => $name,
            'replicas' => $replicas,
            'output' => $result['output'],
        ]);

        return $result;
    }

    /**
     * Get status of a Deployment.
     */
    public function getDeploymentStatus(string $name): ?array
    {
        try {
            $result = $this->kubectl(['get', 'deployment', $name, '-o', 'json']);
            $data = json_decode($result['output'], true);

            return $data ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get pod status for pods matching a label selector.
     */
    public function getPods(string $selector): array
    {
        try {
            $result = $this->kubectl(['get', 'pods', '-l', $selector, '-o', 'json']);
            $data = json_decode($result['output'], true);

            return $data['items'] ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get logs from a pod.
     */
    public function getPodLogs(string $podName, ?string $container = null): string
    {
        $args = ['logs', $podName];

        if ($container) {
            $args[] = '-c';
            $args[] = $container;
        }

        $result = $this->kubectl($args);

        return $result['output'];
    }

    /**
     * Delete a specific resource.
     */
    public function deleteResource(string $kind, string $name): array
    {
        $result = $this->kubectl(['delete', $kind, $name, '--ignore-not-found=true']);

        Log::info('Kubernetes resource deleted', [
            'kind' => $kind,
            'name' => $name,
        ]);

        return $result;
    }

    /**
     * Check if kubectl can connect to the cluster.
     */
    public function isConnected(): bool
    {
        try {
            $this->kubectl(['version']);
            return true;
        } catch (Exception $e) {
            Log::warning('Kubernetes cluster not connected', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a namespace if it doesn't exist.
     */
    public function ensureNamespace(): void
    {
        try {
            $this->kubectl([
                'create',
                'namespace',
                $this->namespace,
                '--dry-run=client',
                '-o',
                'yaml',
            ]);
        } catch (Exception $e) {
            // namespace may already exist
        }

        try {
            $this->kubectl([
                'apply',
                '--validate=false',
                '-f',
                '-',
            ], "---\napiVersion: v1\nkind: Namespace\nmetadata:\n  name: {$this->namespace}\n");
        } catch (Exception $e) {
            Log::warning('Could not ensure namespace', [
                'namespace' => $this->namespace,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Legacy: create a single pod. Kept for compatibility with AgentOrchestrator.
     */
    public function createAgentPod(int $executionId, Agent $agent): array
    {
        $podName = "agent-pod-{$executionId}";

        if (!config('kubernetes.enabled', false)) {
            return $this->createMockPod($podName, $agent);
        }

        // This path is deprecated in favor of AgentDeploymentService.
        // Returning a mock for backwards compatibility.
        return $this->createMockPod($podName, $agent);
    }

    protected function createMockPod(string $podName, ?Agent $agent): array
    {
        return [
            'metadata' => [
                'name' => $podName,
                'namespace' => $this->namespace,
                'labels' => ['app' => 'nanobot-agent'],
                'creationTimestamp' => now()->toAtomString(),
            ],
            'spec' => [
                'containers' => [
                    [
                        'name' => 'agent',
                        'image' => config('kubernetes.pod_image', 'nanobot-agent:latest'),
                    ],
                ],
            ],
            'status' => ['phase' => 'Pending'],
        ];
    }

    /**
     * Ensure the kubeconfig file is available in the kubectl home directory.
     */
    protected function prepareKubeconfig(string $home): string
    {
        $kubeDir = $home . '/.kube';
        $targetConfig = $kubeDir . '/config';

        if (!is_dir($kubeDir)) {
            mkdir($kubeDir, 0700, true);
        }

        if ($this->kubeconfig && file_exists($this->kubeconfig)) {
            if (!file_exists($targetConfig) || md5_file($this->kubeconfig) !== md5_file($targetConfig)) {
                copy($this->kubeconfig, $targetConfig);
                chmod($targetConfig, 0600);
            }

            return $targetConfig;
        }

        $defaultConfig = (getenv('HOME') ?: '/home/ubuntu') . '/.kube/config';

        if (file_exists($defaultConfig)) {
            if (!file_exists($targetConfig) || md5_file($defaultConfig) !== md5_file($targetConfig)) {
                copy($defaultConfig, $targetConfig);
                chmod($targetConfig, 0600);
            }

            return $targetConfig;
        }

        return $targetConfig;
    }
}
