<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('template_id')->nullable()->constrained('agent_templates')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('agent_config');
            $table->json('env_variables');
            $table->json('config_template');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index('template_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
