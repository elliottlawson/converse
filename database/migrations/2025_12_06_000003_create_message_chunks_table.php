<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->integer('sequence');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            
            $table->unique(['message_id', 'sequence']);
            $table->index(['message_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_chunks');
    }
};