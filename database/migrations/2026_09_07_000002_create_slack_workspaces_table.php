<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slack_workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('agent_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, active, disabled
            $table->string('slack_app_id')->nullable();
            $table->string('slack_client_id')->nullable();
            $table->text('slack_client_secret')->nullable();
            $table->text('slack_signing_secret')->nullable();
            $table->text('slack_verification_token')->nullable();
            $table->text('slack_bot_token')->nullable();
            $table->string('slack_team_id')->nullable();
            $table->string('slack_team_name')->nullable();
            $table->string('slack_bot_user_id')->nullable();
            $table->string('storage_slug')->unique();
            $table->json('manifest_json')->nullable();
            $table->json('oauth_response_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->text('icon_path')->nullable();
            $table->timestamps();

            $table->index('agent_id');
            $table->index('user_id');
            $table->index('slack_app_id');
            $table->index('slack_team_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slack_workspaces');
    }
};
