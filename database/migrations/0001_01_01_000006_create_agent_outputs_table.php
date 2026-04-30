<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_outputs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('execution_id')->constrained()->cascadeOnDelete();
            $table->enum('file_type', ['result', 'log', 'artifact', 'metadata']);
            $table->string('file_path');
            $table->text('content');
            $table->unsignedInteger('size_bytes');
            $table->timestamps();

            $table->index('execution_id');
            $table->index('file_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_outputs');
    }
};
