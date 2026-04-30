<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->json('input');
            $table->json('context')->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled']);
            $table->unsignedInteger('progress')->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('config_snapshot')->nullable();
            $table->timestamps();

            $table->index('agent_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_executions');
    }
};
