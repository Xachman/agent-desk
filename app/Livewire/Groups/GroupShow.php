<?php

namespace App\Livewire\Groups;

use App\Models\Agent;
use App\Models\AgentDeployment;
use App\Models\AgentSecret;
use App\Models\AgentTemplate;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class GroupShow extends Component
{
    use WithPagination;

    public Group $group;
    public string $activeTab = 'members';

    public string $memberEmail = '';
    public string $memberRole = 'member';

    public function mount(Group $group): void
    {
        if (! Gate::allows('member-group', $group)) {
            abort(403);
        }

        $this->group = $group;
        $this->activeTab = request()->query('tab', 'members');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function addMember(): void
    {
        if (! Gate::allows('admin-group', $this->group)) {
            abort(403);
        }

        $validated = $this->validate([
            'memberEmail' => 'required|email|exists:users,email',
            'memberRole' => 'required|in:member,admin',
        ]);

        $user = User::where('email', $validated['memberEmail'])->firstOrFail();

        if ($this->group->owner_id === $user->id) {
            session()->flash('error', 'The owner is already a member.');
            return;
        }

        \Illuminate\Support\Facades\Log::debug('addMember reached', [
            'group_id' => $this->group->id,
            'user_id' => $user->id,
        ]);

        $this->group->addMember($user, $validated['memberRole']);
        $this->group->refresh();

        $this->memberEmail = '';
        $this->memberRole = 'member';

        session()->flash('message', "{$user->name} added as {$validated['memberRole']}.");
    }

    public function removeMember(string $userId): void
    {
        if (! Gate::allows('admin-group', $this->group)) {
            abort(403);
        }

        $user = User::findOrFail($userId);

        $this->group->removeMember($user);
        session()->flash('message', 'Member removed.');
    }

    public function changeRole(string $userId, string $role): void
    {
        if (! Gate::allows('manage-group', $this->group)) {
            abort(403);
        }

        if (! in_array($role, ['member', 'admin'], true)) {
            abort(400);
        }

        $user = User::findOrFail($userId);
        $this->group->addMember($user, $role);

        session()->flash('message', 'Role updated.');
    }

    public function render()
    {
        $members = $this->group->users()->withPivot('role')->orderBy('name')->get();
        $agents = Agent::where('group_id', $this->group->id)->with('template', 'deployment')->orderBy('name')->get();
        $templates = AgentTemplate::where('group_id', $this->group->id)->orderBy('name')->get();
        $secrets = AgentSecret::where('group_id', $this->group->id)->with('agent')->orderBy('name')->get();
        $deployments = AgentDeployment::where('group_id', $this->group->id)->with('agent')->orderBy('name')->get();

        return view('livewire.groups.show', [
            'members' => $members,
            'agents' => $agents,
            'templates' => $templates,
            'secrets' => $secrets,
            'deployments' => $deployments,
            'canAdmin' => Gate::allows('admin-group', $this->group),
            'canManage' => Gate::allows('manage-group', $this->group),
        ])->layout('layouts.adminlte', ['title' => $this->group->name]);
    }
}
