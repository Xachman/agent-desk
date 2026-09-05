<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_deployments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->default('nousresearch/hermes-agent:v2026.4.30');
            $table->string('namespace')->default('agent-desk');
            $table->json('model_config');
            $table->longText('soul_markdown')->nullable();
            $table->longText('agents_markdown')->nullable();
            $table->longText('config_yaml')->nullable();
            $table->json('env_variables')->nullable();
            $table->json('secrets')->nullable();
            $table->json('resource_limits')->nullable();
            $table->unsignedInteger('replicas')->default(1);
            $table->string('domain')->nullable();
            $table->string('status')->default('pending');
            $table->longText('yaml_snapshot')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deployed_at')->nullable();
            $table->timestamp('last_status_check_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('agent_id');
            $table->index('status');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_deployments');
    }
};
