<?php

namespace App\Services;

use App\Models\AgentDeployment;
use App\Services\KubernetesService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;

class AgentDeploymentService
{
    protected KubernetesService $kubernetes;

    public function __construct(KubernetesService $kubernetes)
    {
        $this->kubernetes = $kubernetes;
    }

    /**
     * Generate the complete Kubernetes manifest for an agent deployment.
     */
    public function generateManifest(AgentDeployment $deployment): array
    {
        $manifest = array_merge(
            $this->pvcs($deployment),
            $this->secrets($deployment),
            $this->configMaps($deployment),
            $this->service($deployment),
            $this->deployment($deployment),
            $this->ingressRoute($deployment),
        );

        $yaml = $this->toYaml($manifest);
        $deployment->update(['yaml_snapshot' => $yaml]);

        return $manifest;
    }

    /**
     * Deploy the agent to the Kubernetes cluster.
     */
    public function deploy(AgentDeployment $deployment): array
    {
        $manifest = $this->generateManifest($deployment);
        $yaml = $this->toYaml($manifest);

        $result = $this->kubernetes->applyManifest($yaml);

        $deployment->update([
            'status' => 'running',
            'deployed_at' => now(),
        ]);

        Log::info('Agent deployment applied to cluster', [
            'deployment_id' => $deployment->id,
            'slug' => $deployment->slug,
            'output' => $result['output'],
        ]);

        return $result;
    }

    /**
     * Remove the agent from the Kubernetes cluster.
     */
    public function destroy(AgentDeployment $deployment): array
    {
        $manifest = $this->generateManifest($deployment);
        $yaml = $this->toYaml($manifest);

        $result = $this->kubernetes->deleteManifest($yaml);

        $deployment->update([
            'status' => 'stopped',
            'replicas' => 0,
        ]);

        Log::info('Agent deployment removed from cluster', [
            'deployment_id' => $deployment->id,
            'slug' => $deployment->slug,
        ]);

        return $result;
    }

    /**
     * Scale the deployment in the cluster.
     */
    public function scale(AgentDeployment $deployment, int $replicas): array
    {
        $result = $this->kubernetes->scaleDeployment("{$deployment->slug}-agent", $replicas);

        $deployment->update(['replicas' => $replicas]);

        $status = $replicas > 0 ? 'running' : 'stopped';
        $deployment->update(['status' => $status]);

        Log::info('Agent deployment scaled in cluster', [
            'deployment_id' => $deployment->id,
            'slug' => $deployment->slug,
            'replicas' => $replicas,
        ]);

        return $result;
    }

    /**
     * Refresh deployment status from the cluster.
     */
    public function refreshStatus(AgentDeployment $deployment): AgentDeployment
    {
        $status = $this->kubernetes->getDeploymentStatus("{$deployment->slug}-agent");

        $replicas = data_get($status, 'spec.replicas', $deployment->replicas);
        $ready = data_get($status, 'status.readyReplicas', 0);
        $available = data_get($status, 'status.availableReplicas', 0);

        $deploymentStatus = $replicas > 0 && $available > 0 ? 'running' : ($replicas > 0 ? 'pending' : 'stopped');

        $deployment->update([
            'replicas' => (int) $replicas,
            'status' => $deploymentStatus,
            'last_status_check_at' => now(),
        ]);

        return $deployment->refresh();
    }

    /**
     * Generate the YAML string for a deployment manifest.
     */
    public function toYaml(array $manifest): string
    {
        $parts = [];
        foreach ($manifest as $doc) {
            $parts[] = trim(Yaml::dump($doc, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
        }

        return "---\n" . implode("\n---\n", $parts);
    }

    /**
     * Kubernetes Secrets for the deployment.
     */
    public function secrets(AgentDeployment $deployment): array
    {
        $secrets = [];

        $storedSecrets = \App\Models\AgentSecret::where('is_active', true)
            ->where(function ($query) use ($deployment) {
                $query->where('user_id', $deployment->user_id);

                if ($deployment->agent_id) {
                    $query->orWhere('agent_id', $deployment->agent_id);
                }
            })
            ->get();

        foreach ($storedSecrets as $secret) {
            $secrets[] = [
                'apiVersion' => 'v1',
                'kind' => 'Secret',
                'metadata' => ['name' => $secret->kubernetes_secret_name],
                'type' => 'Opaque',
                'stringData' => [
                    $secret->key => $secret->value,
                ],
            ];
        }

        return $secrets;
    }

    /**
     * PVCs required by the agent pod.
     */
    public function pvcs(AgentDeployment $deployment): array
    {
        $slug = $deployment->slug;
        $size = data_get($deployment->resource_limits, 'storage', '5Gi');

        return [
            [
                'apiVersion' => 'v1',
                'kind' => 'PersistentVolumeClaim',
                'metadata' => ['name' => "{$slug}-agent-data-pvc"],
                'spec' => [
                    'accessModes' => ['ReadWriteOnce'],
                    'resources' => ['requests' => ['storage' => $size]],
                ],
            ],
            [
                'apiVersion' => 'v1',
                'kind' => 'PersistentVolumeClaim',
                'metadata' => ['name' => "{$slug}-agent-filesystem-db-pvc"],
                'spec' => [
                    'accessModes' => ['ReadWriteOnce'],
                    'resources' => ['requests' => ['storage' => $size]],
                ],
            ],
        ];
    }

    /**
     * ConfigMaps required by the agent pod.
     */
    public function configMaps(AgentDeployment $deployment): array
    {
        $slug = $deployment->slug;

        return [
            [
                'apiVersion' => 'v1',
                'kind' => 'ConfigMap',
                'metadata' => ['name' => "{$slug}-agent-config"],
                'data' => [
                    'config.yaml' => $deployment->config_yaml ?: $this->defaultConfigYaml($deployment),
                ],
            ],
            [
                'apiVersion' => 'v1',
                'kind' => 'ConfigMap',
                'metadata' => ['name' => "{$slug}-agent-soul"],
                'data' => [
                    'SOUL.md' => $deployment->soul_markdown ?: $this->defaultSoulMarkdown($deployment),
                ],
            ],
            [
                'apiVersion' => 'v1',
                'kind' => 'ConfigMap',
                'metadata' => ['name' => "{$slug}-agent-markdown"],
                'data' => [
                    'AGENTS.md' => $deployment->agents_markdown ?: $this->defaultAgentsMarkdown($deployment),
                ],
            ],
            [
                'apiVersion' => 'v1',
                'kind' => 'ConfigMap',
                'metadata' => ['name' => "{$slug}-agent-docker-daemon"],
                'data' => [
                    'daemon.json' => json_encode(['fixed-cidr' => '172.17.0.0/16']),
                ],
            ],
            [
                'apiVersion' => 'v1',
                'kind' => 'ConfigMap',
                'metadata' => ['name' => "{$slug}-nginx-config"],
                'data' => [
                    'nginx.conf' => $this->nginxConfig($deployment),
                ],
            ],
        ];
    }

    /**
     * Service exposing the agent/nginx sidecar.
     */
    public function service(AgentDeployment $deployment): array
    {
        $slug = $deployment->slug;

        return [
            [
                'apiVersion' => 'v1',
                'kind' => 'Service',
                'metadata' => [
                    'name' => $slug,
                    'labels' => ['app' => $slug],
                ],
                'spec' => [
                    'type' => 'ClusterIP',
                    'selector' => ['app' => 'agent'],
                    'ports' => [
                        [
                            'name' => 'http',
                            'port' => 80,
                            'targetPort' => 80,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Main Deployment with init container, agent, dind, and nginx sidecars.
     */
    public function deployment(AgentDeployment $deployment): array
    {
        $slug = $deployment->slug;
        $image = $deployment->image;
        $limits = $deployment->resource_limits ?: [];
        $agentLimits = data_get($limits, 'agent.limits', ['memory' => '8000Mi', 'cpu' => '2000m']);
        $agentRequests = data_get($limits, 'agent.requests', ['memory' => '500Mi', 'cpu' => '500m']);
        $dindLimits = data_get($limits, 'dind.limits', ['memory' => '4000Mi', 'cpu' => '2000m']);
        $dindRequests = data_get($limits, 'dind.requests', ['memory' => '1000Mi', 'cpu' => '500m']);

        return [
            [
                'apiVersion' => 'apps/v1',
                'kind' => 'Deployment',
                'metadata' => ['name' => "{$slug}-agent"],
                'spec' => [
                    'replicas' => $deployment->replicas,
                    'strategy' => ['type' => 'Recreate'],
                    'selector' => [
                        'matchLabels' => ['app' => 'agent'],
                    ],
                    'template' => [
                        'metadata' => ['labels' => ['app' => 'agent']],
                        'spec' => [
                            'securityContext' => [
                                'fsGroup' => 10000,
                                'supplementalGroups' => [10000],
                            ],
                            'initContainers' => [
                                [
                                    'name' => 'setup-venv',
                                    'image' => 'nousresearch/hermes-agent:v2026.6.5',
                                    'command' => ['bash', '-c'],
                                    'args' => [
                                        "apt update\napt install -y python3-venv\npython3 -m venv /opt/data/.venv\nchown -R 10000:10000 /opt/data/.venv\nsu - hermes -c 'export HOME=/opt/data && curl -fsSL https://opencode.ai/install | bash'",
                                    ],
                                    'volumeMounts' => $this->agentVolumeMounts($deployment),
                                ],
                            ],
                            'containers' => [
                                [
                                    'name' => 'dind',
                                    'image' => 'docker:27-dind-rootless',
                                    'args' => ['--host=tcp://0.0.0.0:2375'],
                                    'securityContext' => [
                                        'privileged' => true,
                                        'runAsUser' => 1000,
                                        'runAsGroup' => 1000,
                                    ],
                                    'ports' => [
                                        ['containerPort' => 2375],
                                    ],
                                    'env' => [
                                        ['name' => 'DOCKER_TLS_CERTDIR', 'value' => ''],
                                    ],
                                    'volumeMounts' => [
                                        ['name' => 'data-volume', 'mountPath' => '/opt/data'],
                                        ['name' => 'docker-daemon-config', 'mountPath' => '/home/rootless/.config/docker/daemon.json', 'subPath' => 'daemon.json'],
                                    ],
                                    'resources' => [
                                        'limits' => $dindLimits,
                                        'requests' => $dindRequests,
                                    ],
                                ],
                                [
                                    'name' => 'agent',
                                    'image' => $image,
                                    'args' => ['gateway', 'run'],
                                    'imagePullPolicy' => 'Always',
                                    'ports' => [
                                        ['containerPort' => 8080],
                                    ],
                                    'env' => $this->buildEnv($deployment),
                                    'volumeMounts' => $this->agentVolumeMounts($deployment),
                                    'resources' => [
                                        'limits' => $agentLimits,
                                        'requests' => $agentRequests,
                                    ],
                                ],
                                [
                                    'name' => 'nginx',
                                    'image' => 'nginx:1.29-alpine',
                                    'ports' => [
                                        ['containerPort' => 80, 'name' => 'http'],
                                    ],
                                    'volumeMounts' => [
                                        [
                                            'name' => "{$slug}-nginx-config",
                                            'mountPath' => '/etc/nginx/nginx.conf',
                                            'subPath' => 'nginx.conf',
                                            'readOnly' => true,
                                        ],
                                    ],
                                    'resources' => [
                                        'limits' => ['memory' => '128Mi', 'cpu' => '100m'],
                                        'requests' => ['memory' => '32Mi', 'cpu' => '10m'],
                                    ],
                                    'livenessProbe' => [
                                        'httpGet' => ['path' => '/healthz', 'port' => 80],
                                        'initialDelaySeconds' => 5,
                                        'periodSeconds' => 10,
                                    ],
                                    'readinessProbe' => [
                                        'httpGet' => ['path' => '/healthz', 'port' => 80],
                                        'initialDelaySeconds' => 3,
                                        'periodSeconds' => 5,
                                    ],
                                ],
                            ],
                            'volumes' => [
                                ['name' => "{$slug}-agent-config", 'configMap' => ['name' => "{$slug}-agent-config"]],
                                ['name' => "{$slug}-agent-soul", 'configMap' => ['name' => "{$slug}-agent-soul"]],
                                ['name' => "{$slug}-agent-markdown", 'configMap' => ['name' => "{$slug}-agent-markdown"]],
                                ['name' => 'data-volume', 'persistentVolumeClaim' => ['claimName' => "{$slug}-agent-data-pvc"]],
                                ['name' => 'filebrowser-db', 'persistentVolumeClaim' => ['claimName' => "{$slug}-agent-filesystem-db-pvc"]],
                                ['name' => 'filebrowser-config', 'emptyDir' => []],
                                ['name' => 'docker-daemon-config', 'configMap' => ['name' => "{$slug}-agent-docker-daemon"]],
                                ['name' => "{$slug}-nginx-config", 'configMap' => ['name' => "{$slug}-nginx-config"]],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Traefik IngressRoute for the agent.
     */
    public function ingressRoute(AgentDeployment $deployment): array
    {
        $slug = $deployment->slug;
        $domain = $deployment->domain ?: "agent-services.example.com";

        return [
            [
                'apiVersion' => 'traefik.io/v1alpha1',
                'kind' => 'IngressRoute',
                'metadata' => ['name' => $slug],
                'spec' => [
                    'entryPoints' => ['web', 'websecure'],
                    'routes' => [
                        [
                            'match' => "HostRegexp(`^[0-9]+-p-{$slug}.{preg_quote($domain)}$`)",
                            'kind' => 'Rule',
                            'priority' => 100,
                            'services' => [
                                [
                                    'name' => $slug,
                                    'port' => 80,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Volume mounts used by the agent/init containers.
     */
    protected function agentVolumeMounts(AgentDeployment $deployment): array
    {
        $slug = $deployment->slug;

        return [
            [
                'name' => "{$slug}-agent-config",
                'mountPath' => '/opt/data/config.yaml',
                'subPath' => 'config.yaml',
                'readOnly' => true,
            ],
            [
                'name' => "{$slug}-agent-soul",
                'mountPath' => '/opt/data/SOUL.md',
                'subPath' => 'SOUL.md',
                'readOnly' => true,
            ],
            [
                'name' => "{$slug}-agent-markdown",
                'mountPath' => '/opt/data/AGENTS.md',
                'subPath' => 'AGENTS.md',
                'readOnly' => true,
            ],
            [
                'name' => 'data-volume',
                'mountPath' => '/opt/data',
            ],
        ];
    }

    /**
     * Build container env array, merging user-provided env vars and secret refs.
     */
    protected function buildEnv(AgentDeployment $deployment): array
    {
        $env = [
            ['name' => 'DOCKER_HOST', 'value' => 'tcp://localhost:2375'],
        ];

        $userEnv = $deployment->env_variables ?: [];
        foreach ($userEnv as $key => $value) {
            $env[] = ['name' => $key, 'value' => (string) $value];
        }

        $manualSecrets = $deployment->secrets ?: [];
        foreach ($manualSecrets as $secret) {
            if (empty($secret['name']) || empty($secret['secret_name']) || empty($secret['key'])) {
                continue;
            }

            $env[] = [
                'name' => $secret['name'],
                'valueFrom' => [
                    'secretKeyRef' => [
                        'name' => $secret['secret_name'],
                        'key' => $secret['key'],
                    ],
                ],
            ];
        }

        $storedSecrets = \App\Models\AgentSecret::where('is_active', true)
            ->where(function ($query) use ($deployment) {
                $query->where('user_id', $deployment->user_id);

                if ($deployment->agent_id) {
                    $query->orWhere('agent_id', $deployment->agent_id);
                }
            })
            ->get();

        foreach ($storedSecrets as $secret) {
            $env[] = [
                'name' => $secret->name,
                'valueFrom' => [
                    'secretKeyRef' => [
                        'name' => $secret->kubernetes_secret_name,
                        'key' => $secret->key,
                    ],
                ],
            ];
        }

        return $env;
    }

    /**
     * Default config.yaml content when none is provided.
     */
    protected function defaultConfigYaml(AgentDeployment $deployment): string
    {
        $model = $deployment->model_config ?: [];
        $default = data_get($model, 'default', 'kimi-k2.6:cloud');
        $provider = data_get($model, 'provider', 'ollama-cloud');
        $apiMode = data_get($model, 'api_mode', 'chat_completions');

        return <<<YAML
model:
  default: {$default}
  provider: {$provider}
  api_mode: {$apiMode}
terminal:
  backend: local
  docker_image: nikolaik/python-nodejs:python3.11-nodejs20
  docker_volumes:
    - {$deployment->slug}-agent-data-pvc:/opt/data
  docker_extra_args:
    - --network=host
platforms:
  slack:
    reply_to_mode: "first"
    extra:
      reply_in_thread: true
      reply_broadcast: false
logging:
  level: "DEBUG"
YAML;
    }

    protected function defaultSoulMarkdown(AgentDeployment $deployment): string
    {
        $name = $deployment->name;

        return <<<MD
## Agent Identity
- Name: {$name}

## Personality
{$name} communicates in a way that is:
- Concise
- Clear
- Pleasant
- Professional

## Communication Style
- Keeps responses brief and to the point
- Avoids unnecessary verbosity
- Maintains a friendly and approachable tone
- Prioritizes clarity over complexity
MD;
    }

    protected function defaultAgentsMarkdown(AgentDeployment $deployment): string
    {
        return <<<MD
## Agent Overview
You are a helpful AI agent running in Kubernetes.

## Working Environment
- Timezone: UTC

## Responsibilities
- Assist users via configured platforms
- Execute tasks safely inside the container

## Behavior Expectations
- Communicate clearly and concisely
- Use Docker for software/website builds when asked
- Expose ports via the configured domain pattern
MD;
    }

    /**
     * Generate nginx sidecar config for port-based subdomains.
     */
    protected function nginxConfig(AgentDeployment $deployment): string
    {
        $slug = $deployment->slug;
        $domain = $deployment->domain ?: "agent-services.example.com";

        return <<<NGINX
events {}

http {
  resolver kube-dns.kube-system.svc.cluster.local valid=10s;

  log_format main '\$remote_addr - \$remote_user [\$time_local] "\$request" '
                  '\$status \$body_bytes_sent "\$http_referer" '
                  '"\$http_user_agent" host=\$host backend=\$backend_port';

  access_log /var/log/nginx/access.log main;

  map \$host \$backend_port {
    default "";
    ~^(?<port>[0-9]+)-p-{$slug}\.{preg_quote($domain)}$ \$port;
  }

  server {
    listen 80;

    server_name ~^[0-9]+-p-{$slug}\.{preg_quote($domain)}$;

    location /healthz {
      access_log off;
      return 200 "healthy\n";
    }

    location / {
      proxy_pass http://127.0.0.1:\$backend_port;

      proxy_http_version 1.1;

      proxy_set_header Host \$host;
      proxy_set_header X-Real-IP \$remote_addr;
      proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
      proxy_set_header X-Forwarded-Proto \$scheme;

      proxy_set_header Upgrade \$http_upgrade;
      proxy_set_header Connection "upgrade";
    }
  }
}
NGINX;
    }
}
