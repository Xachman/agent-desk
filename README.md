# Agent Desk

Agent Desk is a Laravel-based admin dashboard for managing AI agents deployed in Kubernetes. It provides a web interface to create agents from templates, store secrets, deploy agents to a Kubernetes cluster, scale them, monitor their status, and execute agent runs.

## Features

- **Agent Management**: Create, edit, and run AI agents from reusable templates
- **Templates**: Build agent blueprints with system prompts, config, environment variables, and tool definitions
- **Kubernetes Secrets**: Store secrets that are automatically injected into agent deployments
- **Kubernetes Deployments**: Deploy, scale, and destroy agents directly in a Kubernetes cluster
- **Live Cluster Status**: View deployment status, pod health, and generated Kubernetes manifests
- **AdminLTE-style UI**: Clean sidebar, top navigation, cards, and data tables
- **Role-based Access**: Admin and regular user roles

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+ and npm
- SQLite (default) or MySQL/PostgreSQL
- `kubectl` installed and configured for Kubernetes access
- A Kubernetes cluster (local, cloud, or on-prem)

## Quick Start

### 1. Clone and install dependencies

```bash
git clone https://github.com/your-org/agent-desk.git
cd agent-desk
composer install
npm install
npm run build
```

### 2. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure at minimum:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
```

### 3. Database

```bash
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed   # optional
```

### 4. Start the server

```bash
php artisan serve
```

Visit `http://localhost:8000` and log in with the default admin account created during setup, or use the seeder.

## Connecting to Kubernetes

Agent Desk uses `kubectl` to interact with your Kubernetes cluster. This means any cluster that works with `kubectl` will work with Agent Desk.

### 1. Install kubectl

**Linux:**
```bash
curl -LO "https://dl.k8s/release/$(curl -L -s https://dl.k8s/release/stable.txt)/bin/linux/amd64/kubectl"
chmod +x kubectl
sudo mv kubectl /usr/local/bin/
```

**macOS:**
```bash
brew install kubectl
```

**Windows:**
```powershell
winget install -e --id Kubernetes.kubectl
```

### 2. Get your kubeconfig file

Your kubeconfig is normally located at `~/.kube/config`. If you do not have one, generate it from your Kubernetes provider:

| Provider | Command |
|----------|---------|
| k3s | `sudo cp /etc/rancher/k3s/k3s.yaml ~/.kube/config && sudo chown $(whoami):$(whoami) ~/.kube/config` |
| minikube | `minikube update-context` |
| AWS EKS | `aws eks update-kubeconfig --region us-east-1 --name my-cluster` |
| Google GKE | `gcloud container clusters get-credentials my-cluster --region us-east1` |
| Azure AKS | `az aks get-credentials --resource-group my-rg --name my-cluster` |
| DigitalOcean / Linode / Vultr | Download the kubeconfig from the provider dashboard |

### 3. Test kubectl locally

```bash
kubectl get nodes
kubectl get namespaces
```

If these commands work, Agent Desk can connect.

### 4. Configure Agent Desk

Add the Kubernetes settings to your `.env` file:

```env
KUBERNETES_ENABLED=true
KUBECONFIG=/home/ubuntu/.kube/config
KUBECTL_CONTEXT=                    # optional: only if you have multiple contexts
KUBERNETES_NAMESPACE=agent-desk
KUBERNETES_DOMAIN=agent-services.example.com
AGENT_POD_IMAGE=nousresearch/hermes-agent:v2026.4.30
AGENT_PVC_SIZE=5Gi
AGENT_EXECUTION_TIMEOUT=3600
```

- `KUBECONFIG` — absolute path to your kubeconfig file
- `KUBECTL_CONTEXT` — context name within the kubeconfig (leave blank to use the current context)
- `KUBERNETES_NAMESPACE` — namespace where agents will be deployed
- `KUBERNETES_DOMAIN` — base domain used for IngressRoute subdomains
- `AGENT_POD_IMAGE` — Docker image used by agent pods
- `AGENT_PVC_SIZE` — storage size for agent PVCs
- `AGENT_EXECUTION_TIMEOUT` — maximum pod runtime in seconds

### 5. Verify the connection

```bash
php artisan config:clear
php artisan tinker --execute='echo app(App\Services\KubernetesService::class)->isConnected() ? "connected" : "not connected";'
```

Expected output: `connected`

### 6. Create the namespace

Agent Desk will target the namespace configured in `.env`. You can create it manually:

```bash
kubectl create namespace agent-desk
```

Or the app will attempt to apply resources into it when you deploy.

## Deploying Your First Agent

1. Log in to Agent Desk
2. Create a **Template** at `/templates`
3. Create an **Agent** at `/dashboard` using the template
4. Create a **Secret** at `/secrets` (optional, e.g. API keys)
5. Create an **Agent Deployment** at `/agent-deployments`
6. Click **Deploy** to apply the generated manifest to Kubernetes
7. Monitor status, pods, and logs from the deployment show page

## Running an Agent

From the dashboard, click **Run** on any agent. Agent Desk will:

1. Ensure a Kubernetes deployment exists for the agent
2. Scale it to at least 1 replica if it is stopped
3. Write the execution payload into the running pod
4. Track the execution status

## Project Structure

```
app/
  Livewire/           # AdminLTE-style page components
    Dashboard/
    AgentDeployments/
    Templates/
    Secrets/
  Models/
    Agent.php
    AgentTemplate.php
    AgentDeployment.php
    AgentSecret.php
    AgentExecution.php
  Services/
    AgentDeploymentService.php   # Generate and deploy K8s manifests
    KubernetesService.php          # kubectl wrapper
    AgentOrchestrator.php          # Run agents in K8s
    AgentTemplateService.php
    AgentFileService.php
    NotificationService.php

resources/views/
  layouts/adminlte.blade.php     # AdminLTE-style layout
  layouts/guest.blade.php        # Login layout
  livewire/                      # Page views

routes/web.php                   # Web routes
database/migrations/             # Database schema
tests/Feature/                   # Feature tests
```

## Testing

```bash
php artisan test
```

Or:

```bash
vendor/bin/phpunit --testsuite Feature
```

## Environment Variables Reference

| Variable | Description | Default |
|----------|-------------|---------|
| `KUBERNETES_ENABLED` | Enable Kubernetes interactions | `false` |
| `KUBECONFIG` | Path to kubectl config file | unset |
| `KUBECTL_CONTEXT` | kubectl context to use | unset |
| `KUBERNETES_NAMESPACE` | Target namespace | `agent-desk` |
| `KUBERNETES_DOMAIN` | Base domain for agent IngressRoute | `agent-services.example.com` |
| `AGENT_POD_IMAGE` | Default agent Docker image | `nousresearch/hermes-agent:v2026.4.30` |
| `AGENT_PVC_SIZE` | Default PVC size | `1Gi` |
| `AGENT_EXECUTION_TIMEOUT` | Pod timeout | `3600` |

## Troubleshooting

### kubectl not found

Ensure `kubectl` is in your system `PATH`:

```bash
which kubectl
```

### Cannot connect to cluster

1. Verify `KUBECONFIG` path is correct
2. Run `kubectl --kubeconfig=/your/path get nodes` manually
3. If using a cloud provider, ensure your credentials are current
4. Check that the configured `KUBECTL_CONTEXT` exists: `kubectl config get-contexts`

### Namespace errors

Create the namespace before deploying:

```bash
kubectl create namespace agent-desk
```

### Permission denied on kubeconfig

```bash
chmod 600 ~/.kube/config
```

## License

MIT
