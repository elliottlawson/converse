<?php

namespace ElliottLawson\Converse\Models;

use ElliottLawson\Converse\Database\Factories\MessageFactory;
use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Enums\MessageStatus;
use ElliottLawson\Converse\Events\ChunkReceived;
use ElliottLawson\Converse\Events\MessageCompleted;
use ElliottLawson\Converse\Events\MessageCreated;
use ElliottLawson\Converse\Events\MessageUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'role',
        'content',
        'metadata',
        'status',
        'is_complete',
        'completed_at',
    ];

    public function getTable()
    {
        return config('converse.tables.messages');
    }

    protected $casts = [
        'role' => MessageRole::class,
        'status' => MessageStatus::class,
        'metadata' => 'array',
        'is_complete' => 'boolean',
        'completed_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'created' => MessageCreated::class,
        'updated' => MessageUpdated::class,
    ];

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(MessageChunk::class)->orderBy('sequence');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function scopeByRole($query, MessageRole $role)
    {
        return $query->where('role', $role);
    }

    public function scopeUser($query)
    {
        return $query->where('role', MessageRole::User);
    }

    public function scopeAssistant($query)
    {
        return $query->where('role', MessageRole::Assistant);
    }

    public function scopeSystem($query)
    {
        return $query->where('role', MessageRole::System);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_complete', true);
    }

    public function scopeStreaming($query)
    {
        return $query->where('is_complete', false);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', MessageStatus::Error);
    }

    public function appendChunk(string $chunk, array $metadata = []): MessageChunk
    {
        $sequence = $this->chunks()->max('sequence') ?? -1;

        $messageChunk = $this->chunks()->create([
            'content' => $chunk,
            'sequence' => $sequence + 1,
            'metadata' => $metadata,
        ]);

        $this->update([
            'content' => $this->content.$chunk,
        ]);

        event(new ChunkReceived($this, $messageChunk));

        return $messageChunk;
    }

    public function completeStreaming(array $finalMetadata = []): self
    {
        $metadata = array_merge($this->metadata ?? [], $finalMetadata);
        $metadata['chunks'] = $this->chunks()->count();

        $this->update([
            'is_complete' => true,
            'completed_at' => now(),
            'status' => MessageStatus::Success,
            'metadata' => $metadata,
        ]);

        event(new MessageCompleted($this));

        return $this;
    }

    public function failStreaming(string $error, array $errorMetadata = []): self
    {
        $metadata = array_merge($this->metadata ?? [], $errorMetadata);
        $metadata['error'] = $error;
        $metadata['chunks'] = $this->chunks()->count();

        $this->update([
            'is_complete' => true,
            'completed_at' => now(),
            'status' => MessageStatus::Error,
            'metadata' => $metadata,
        ]);

        event(new MessageCompleted($this));

        return $this;
    }

    public function markAsError(string $error, array $errorMetadata = []): self
    {
        $metadata = array_merge($this->metadata ?? [], $errorMetadata);
        $metadata['error'] = $error;

        $this->update([
            'status' => MessageStatus::Error,
            'is_complete' => true,
            'completed_at' => now(),
            'metadata' => $metadata,
        ]);

        return $this;
    }

    public function isToolCall(): bool
    {
        return $this->role === MessageRole::ToolCall;
    }

    public function isToolResult(): bool
    {
        return $this->role === MessageRole::ToolResult;
    }

    public function getToolCallId(): ?string
    {
        return $this->metadata['tool_call_id'] ?? null;
    }

    public function getToolName(): ?string
    {
        return $this->metadata['tool_name'] ?? null;
    }
}
