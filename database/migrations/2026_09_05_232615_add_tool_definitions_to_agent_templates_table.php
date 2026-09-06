<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_templates', function (Blueprint $table) {
            if (Schema::hasColumn('agent_templates', 'config_defaults')) {
                $table->renameColumn('config_defaults', 'config');
            }
            if (Schema::hasColumn('agent_templates', 'env_defaults')) {
                $table->renameColumn('env_defaults', 'env');
            }
            if (!Schema::hasColumn('agent_templates', 'tool_definitions')) {
                $table->json('tool_definitions')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_templates', function (Blueprint $table) {
            if (Schema::hasColumn('agent_templates', 'config')) {
                $table->renameColumn('config', 'config_defaults');
            }
            if (Schema::hasColumn('agent_templates', 'env')) {
                $table->renameColumn('env', 'env_defaults');
            }
        });
    }
};
