<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('converse.tables.messages'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained(config('converse.tables.conversations'))->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system', 'tool_call', 'tool_result']);
            $table->text('content')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('status', ['pending', 'success', 'error'])->default('success');
            $table->boolean('is_complete')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index('role');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('converse.tables.messages'));
    }
};
