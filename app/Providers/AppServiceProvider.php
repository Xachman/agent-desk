<?php

namespace App\Providers;

use App\Services\AgentOrchestrator;
use App\Services\KubernetesService;
use App\Services\NanobotService;
use App\Services\AgentFileService;
use App\Services\AgentTemplateService;
use App\Services\NotificationService;
use App\Services\SlackApiService;
use App\Services\SlackAppProvisioningService;
use App\Services\SlackConfigTokenService;
use App\Services\SlackIconService;
use App\Services\SlackKubernetesRunnerService;
use App\Services\SlackSignatureVerifier;
use App\Services\SlackWorkspaceSecretService;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NanobotService::class);
        $this->app->singleton(KubernetesService::class);
        $this->app->singleton(AgentFileService::class);
        $this->app->singleton(AgentTemplateService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(AgentOrchestrator::class);

        $this->app->singleton(SlackConfigTokenService::class);
        $this->app->singleton(SlackIconService::class);
        $this->app->singleton(SlackSignatureVerifier::class);
        $this->app->singleton(SlackApiService::class);
        $this->app->singleton(SlackAppProvisioningService::class);
        $this->app->singleton(SlackWorkspaceSecretService::class);
        $this->app->singleton(SlackKubernetesRunnerService::class);
    }

    public function boot(): void
    {
        $this->enforceHttps();
    }

    private function enforceHttps(): void
    {
        $protocol = Request::header('X-Forwarded-Proto');

        if (Request::secure() || (is_string($protocol) && strtolower($protocol) === 'https')) {
            URL::forceScheme('https');
        }
    }
}
