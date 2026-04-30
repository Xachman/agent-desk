<?php

namespace App\Providers;

use App\Services\AgentOrchestrator;
use App\Services\KubernetesService;
use App\Services\NanobotService;
use App\Services\AgentFileService;
use App\Services\AgentTemplateService;
use App\Services\NotificationService;
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
    }

    public function boot(): void
    {
        //
    }
}
