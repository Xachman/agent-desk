<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slack_platform_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->default('default');
            $table->text('config_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->dateTime('config_token_expires_at')->nullable();
            $table->string('team_id')->nullable();
            $table->string('user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slack_platform_configs');
    }
};
