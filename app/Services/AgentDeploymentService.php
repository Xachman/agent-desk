<?php

namespace App\Services;

use App\Models\AgentDeployment;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;

class AgentDeploymentService
{
    /**
     * Generate the complete Kubernetes manifest for an agent deployment.
     */
    public function generateManifest(AgentDeployment $deployment): array
    {
        $manifest = array_merge(
            $this->pvcs($deployment),
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

        $secrets = $deployment->secrets ?: [];
        foreach ($secrets as $secret) {
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
