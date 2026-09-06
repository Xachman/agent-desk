<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\AgentDeployment;
use App\Models\AgentSecret;
use App\Models\AgentTemplate;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class GroupServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Boot any application services.
     */
    public function boot(): void
    {
        $this->registerGroupGates();
    }

    protected function registerGroupGates(): void
    {
        Gate::define('manage-group', function (User $user, Group $group) {
            return $group->owner_id == $user->id || $user->role === 'admin';
        });

        Gate::define('admin-group', function (User $user, Group $group) {
            $role = $group->memberRole($user);

            return $group->owner_id == $user->id
                || $role === 'admin'
                || $user->role === 'admin';
        });

        Gate::define('member-group', function (User $user, Group $group) {
            $role = $group->memberRole($user);

            return $group->owner_id == $user->id
                || in_array($role, ['admin', 'member'], true)
                || $user->role === 'admin';
        });

        Gate::define('create-agent', function (User $user, ?Group $group = null) {
            if (!$group) {
                return true;
            }

            return Gate::allows('admin-group', $group);
        });

        Gate::define('update-agent', function (User $user, Agent $agent) {
            return $this->canModifyResource($user, $agent);
        });

        Gate::define('delete-agent', function (User $user, Agent $agent) {
            return $this->canModifyResource($user, $agent);
        });

        Gate::define('create-template', function (User $user, ?Group $group = null) {
            if (!$group) {
                return true;
            }

            return Gate::allows('admin-group', $group);
        });

        Gate::define('update-template', function (User $user, AgentTemplate $template) {
            if ($template->group_id === null) {
                return $template->user_id === $user->id || $template->user_id === null || $user->isAdmin();
            }

            return $this->canModifyResource($user, $template);
        });

        Gate::define('delete-template', function (User $user, AgentTemplate $template) {
            if ($template->group_id === null) {
                return $template->user_id === $user->id || $template->user_id === null || $user->isAdmin();
            }

            return $this->canModifyResource($user, $template);
        });

        Gate::define('create-secret', function (User $user, ?Group $group = null) {
            if (!$group) {
                return true;
            }

            return Gate::allows('admin-group', $group);
        });

        Gate::define('update-secret', function (User $user, AgentSecret $secret) {
            return $this->canModifyResource($user, $secret);
        });

        Gate::define('delete-secret', function (User $user, AgentSecret $secret) {
            return $this->canModifyResource($user, $secret);
        });

        Gate::define('manage-deployment', function (User $user, AgentDeployment $deployment) {
            return $this->canModifyResource($user, $deployment);
        });

        Gate::define('interact-deployment', function (User $user, AgentDeployment $deployment) {
            if ($deployment->group_id === null) {
                return $deployment->user_id === $user->id || $user->role === 'admin';
            }

            return Gate::allows('member-group', $deployment->group);
        });

        Gate::define('view-group-resources', function (User $user, Group $group) {
            return Gate::allows('member-group', $group);
        });
    }

    /**
     * Determine if a user can modify a group-owned resource.
     */
    protected function canModifyResource(User $user, Agent|AgentTemplate|AgentSecret|AgentDeployment $resource): bool
    {
        if ($resource->group_id === null) {
            return $resource->user_id == $user->id || $user->role === 'admin';
        }

        if (!$resource->group) {
            return $user->role === 'admin';
        }

        return Gate::allows('admin-group', $resource->group);
    }
}
