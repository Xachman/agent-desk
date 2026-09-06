<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_secrets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('agent_id')->nullable()->constrained('agents')->cascadeOnDelete();
            $table->string('name');
            $table->string('kubernetes_secret_name');
            $table->string('key');
            $table->text('value');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index('agent_id');
            $table->unique(['agent_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_secrets');
    }
};
