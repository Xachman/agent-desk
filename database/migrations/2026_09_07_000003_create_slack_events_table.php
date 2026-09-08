<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slack_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('slack_workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('agent_execution_id')->nullable()->constrained('agent_executions')->nullOnDelete();
            $table->string('slack_event_id')->nullable()->unique();
            $table->string('type');
            $table->string('subtype')->nullable();
            $table->string('channel_id')->nullable();
            $table->string('user_id')->nullable();
            $table->text('text')->nullable();
            $table->json('payload');
            $table->string('status')->default('received'); // received, processing, completed, failed, ignored
            $table->text('error_message')->nullable();
            $table->json('response_payload')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();

            $table->index('slack_workspace_id');
            $table->index('slack_event_id');
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slack_events');
    }
};
