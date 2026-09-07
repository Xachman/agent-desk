<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure every user has a username and a personal group
        foreach (User::whereNull('username')->orWhereDoesntHave('personalGroup')->get() as $user) {
            if (empty($user->username)) {
                $user->username = User::generateUniqueUsername($user->name);
                $user->save();
            }

            $user->personalGroup()->firstOrCreate(
                ['owner_id' => $user->id],
                [
                    'name' => $user->name ?: $user->username,
                    'slug' => $user->username,
                    'description' => 'Personal workspace for ' . ($user->name ?: $user->username),
                ]
            );
        }

        // Backfill null group_ids with the owner's personal group
        DB::statement('UPDATE agents SET group_id = (SELECT id FROM groups WHERE groups.owner_id = agents.user_id LIMIT 1) WHERE group_id IS NULL');
        DB::statement('UPDATE agent_templates SET group_id = (SELECT id FROM groups WHERE groups.owner_id = agent_templates.user_id LIMIT 1) WHERE group_id IS NULL');
        DB::statement('UPDATE agent_secrets SET group_id = (SELECT id FROM groups WHERE groups.owner_id = agent_secrets.user_id LIMIT 1) WHERE group_id IS NULL');
        DB::statement('UPDATE agent_deployments SET group_id = (SELECT id FROM groups WHERE groups.owner_id = agent_deployments.user_id LIMIT 1) WHERE group_id IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: group_id columns are required by earlier migrations after this point.
    }
};
