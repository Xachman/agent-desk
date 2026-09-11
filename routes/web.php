<?php

use App\Http\Controllers\Slack\SlackAgentCallbackController;
use App\Http\Controllers\Slack\SlackEventController;
use App\Http\Controllers\Slack\SlackOAuthController;
use App\Livewire\Slack\AgentSlackSettings;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Livewire routes
Route::get('/login', \App\Livewire\Login::class)->name('login');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', \App\Livewire\Dashboard\Dashboard::class)->name('dashboard');

    Route::get('/groups', \App\Livewire\Groups\GroupIndex::class)->name('groups.index');
    Route::get('/groups/create', \App\Livewire\Groups\GroupCreate::class)->name('groups.create');
    Route::get('/groups/{group}', \App\Livewire\Groups\GroupShow::class)->name('groups.show');

    Route::get('/templates', \App\Livewire\Templates\TemplateIndex::class)->name('agent-templates.index');
    Route::get('/templates/create', \App\Livewire\Templates\TemplateCreate::class)->name('agent-templates.create');
    Route::get('/templates/{template}/edit', \App\Livewire\Templates\TemplateEdit::class)->name('agent-templates.edit');

    Route::get('/secrets', \App\Livewire\Secrets\SecretIndex::class)->name('secrets.index');
    Route::get('/secrets/create', \App\Livewire\Secrets\SecretCreate::class)->name('secrets.create');
    Route::get('/secrets/{secret}/edit', \App\Livewire\Secrets\SecretEdit::class)->name('secrets.edit');
});



Route::middleware(['auth'])->group(function () {
    Route::get('/agent-deployments', \App\Livewire\AgentDeployments\AgentDeploymentIndex::class)->name('agent-deployments.index');
    Route::get('/agent-deployments/{deployment}', \App\Livewire\AgentDeployments\AgentDeploymentShow::class)->name('agent-deployments.show');

    Route::get('/agents/{agent}/slack', AgentSlackSettings::class)->name('slack.settings');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::view('/admin', 'livewire.admin')->name('admin');

    Route::get('/agent-deployments/create', \App\Livewire\AgentDeployments\AgentDeploymentCreate::class)->name('agent-deployments.create');
    Route::get('/agent-deployments/{deployment}/edit', \App\Livewire\AgentDeployments\AgentDeploymentEdit::class)->name('agent-deployments.edit');
});

Route::post('/slack/oauth/callback', [SlackOAuthController::class, 'callback'])->name('slack.oauth.callback');
