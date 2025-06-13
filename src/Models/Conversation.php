<?php

namespace ElliottLawson\Converse\Models;

use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Enums\MessageStatus;
use ElliottLawson\Converse\Events\ConversationCreated;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Conversation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'metadata',
        'context',
    ];

    public function getTable()
    {
        return config('converse.tables.conversations');
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function newFactory()
    {
        return \ElliottLawson\Converse\Database\Factories\ConversationFactory::new();
    }

    protected $casts = [
        'metadata' => 'array',
        'context' => 'array',
    ];

    protected $dispatchesEvents = [
        'created' => ConversationCreated::class,
    ];

    protected static function booted(): void
    {
        static::deleting(function (Conversation $conversation) {
            if ($conversation->isForceDeleting()) {
                $conversation->messages()->withTrashed()->forceDelete();
            } else {
                $conversation->messages()->delete();
            }
        });

        static::restoring(function (Conversation $conversation) {
            $conversation->messages()->withTrashed()->restore();
        });
    }

    public function conversable(): MorphTo
    {
        return $this->morphTo();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function addMessage(MessageRole $role, ?string $content = null, array $metadata = []): Message
    {
        return $this->messages()->create([
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
            'status' => MessageStatus::Success,
            'is_complete' => ! empty($content),
            'completed_at' => ! empty($content) ? now() : null,
        ]);
    }

    public function addUserMessage(string $content, array $metadata = []): Message
    {
        return $this->addMessage(MessageRole::User, $content, $metadata);
    }

    public function addAssistantMessage(string $content, array $metadata = []): Message
    {
        return $this->addMessage(MessageRole::Assistant, $content, $metadata);
    }

    public function addSystemMessage(string $content, array $metadata = []): Message
    {
        return $this->addMessage(MessageRole::System, $content, $metadata);
    }

    public function addToolCallMessage(string $content, array $metadata = []): Message
    {
        return $this->addMessage(MessageRole::ToolCall, $content, $metadata);
    }

    public function addToolResultMessage(string $content, array $metadata = []): Message
    {
        return $this->addMessage(MessageRole::ToolResult, $content, $metadata);
    }

    public function startStreamingMessage(MessageRole $role, array $metadata = []): Message
    {
        $metadata['streamed'] = true;

        return $this->messages()->create([
            'role' => $role,
            'content' => '',
            'metadata' => $metadata,
            'status' => MessageStatus::Pending,
            'is_complete' => false,
        ]);
    }

    public function startStreamingAssistant(array $metadata = []): Message
    {
        return $this->startStreamingMessage(MessageRole::Assistant, $metadata);
    }

    public function startStreamingUser(array $metadata = []): Message
    {
        return $this->startStreamingMessage(MessageRole::User, $metadata);
    }

    public function addMessages(...$messages): Collection
    {
        // If first argument is an array and it's the only argument, use it as the messages
        if (count($messages) === 1 && is_array($messages[0])) {
            $messages = $messages[0];
        }

        $createdMessages = collect();

        foreach ($messages as $message) {
            // Handle different formats
            if (is_string($message)) {
                // Simple string assumes user message
                $createdMessages->push($this->addUserMessage($message));
            } elseif (is_object($message)) {
                // Check if it's one of our message DTOs
                if (method_exists($message, 'toArray')) {
                    $data = $message->toArray();
                    $createdMessages->push($this->addMessage($data['role'], $data['content'], $data['metadata'] ?? []));
                } else {
                    throw new \InvalidArgumentException('Unknown message object type: '.get_class($message));
                }
            } elseif (isset($message['role']) && isset($message['content'])) {
                // Array with role and content
                $role = $message['role'] instanceof MessageRole ? $message['role'] : MessageRole::from($message['role']);
                $metadata = $message['metadata'] ?? [];

                $createdMessages->push($this->addMessage($role, $message['content'], $metadata));
            } elseif (isset($message['type']) && isset($message['content'])) {
                // Alternative format with type
                $metadata = $message['metadata'] ?? [];

                $createdMessages->push(match ($message['type']) {
                    'user' => $this->addUserMessage($message['content'], $metadata),
                    'assistant' => $this->addAssistantMessage($message['content'], $metadata),
                    'system' => $this->addSystemMessage($message['content'], $metadata),
                    'tool_call' => $this->addToolCallMessage($message['content'], $metadata),
                    'tool_result' => $this->addToolResultMessage($message['content'], $metadata),
                    default => throw new \InvalidArgumentException("Unknown message type: {$message['type']}")
                });
            }
        }

        return $createdMessages;
    }
}
