<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('converse.tables.message_attachments'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained(config('converse.tables.messages'))->cascadeOnDelete();
            $table->string('type');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->integer('size')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['message_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('converse.tables.message_attachments'));
    }
};
