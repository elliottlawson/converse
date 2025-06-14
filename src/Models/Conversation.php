<?php

namespace ElliottLawson\Converse\Models;

use ElliottLawson\Converse\Database\Factories\ConversationFactory;
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
use Illuminate\Support\Traits\Conditionable;
use Illuminate\View\View;

class Conversation extends Model
{
    use Conditionable, HasFactory, HasUuids, SoftDeletes;

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

    protected static function newFactory(): ConversationFactory
    {
        return ConversationFactory::new();
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

    protected function normalizeContent(string|View|null $content): ?string
    {
        if ($content instanceof View) {
            return $content->render();
        }

        return $content;
    }

    public function addMessage(MessageRole $role, string|View|null $content = null, array $metadata = []): self
    {
        $normalizedContent = $this->normalizeContent($content);

        $this->messages()->create([
            'role' => $role,
            'content' => $normalizedContent,
            'metadata' => $metadata,
            'status' => MessageStatus::Success,
            'is_complete' => filled($normalizedContent),
            'completed_at' => filled($normalizedContent) ? now() : null,
        ]);

        return $this;
    }

    public function addUserMessage(string|View $content, array $metadata = []): self
    {
        return $this->addMessage(MessageRole::User, $content, $metadata);
    }

    public function addAssistantMessage(string|View $content, array $metadata = []): self
    {
        return $this->addMessage(MessageRole::Assistant, $content, $metadata);
    }

    public function addSystemMessage(string|View $content, array $metadata = []): self
    {
        return $this->addMessage(MessageRole::System, $content, $metadata);
    }

    public function addToolCallMessage(string|View $content, array $metadata = []): self
    {
        return $this->addMessage(MessageRole::ToolCall, $content, $metadata);
    }

    public function addToolResultMessage(string|View $content, array $metadata = []): self
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
                $createdMessages->push($this->createUserMessage($message));
            } elseif (is_object($message)) {
                // Check if it's one of our message DTOs
                if (method_exists($message, 'toArray')) {
                    $data = $message->toArray();
                    $createdMessages->push($this->createMessage($data['role'], $data['content'], $data['metadata'] ?? []));
                } else {
                    throw new \InvalidArgumentException('Unknown message object type: '.get_class($message));
                }
            } elseif (isset($message['role']) && isset($message['content'])) {
                // Array with role and content
                $role = $message['role'] instanceof MessageRole ? $message['role'] : MessageRole::from($message['role']);
                $metadata = $message['metadata'] ?? [];

                $createdMessages->push($this->createMessage($role, $message['content'], $metadata));
            } elseif (isset($message['type']) && isset($message['content'])) {
                // Alternative format with type
                $metadata = $message['metadata'] ?? [];

                $createdMessages->push(match ($message['type']) {
                    'user' => $this->createUserMessage($message['content'], $metadata),
                    'assistant' => $this->createAssistantMessage($message['content'], $metadata),
                    'system' => $this->createSystemMessage($message['content'], $metadata),
                    'tool_call' => $this->createToolCallMessage($message['content'], $metadata),
                    'tool_result' => $this->createToolResultMessage($message['content'], $metadata),
                    default => throw new \InvalidArgumentException("Unknown message type: {$message['type']}")
                });
            }
        }

        return $createdMessages;
    }

    public function selectRecentMessages(int $count): self
    {
        $clone = clone $this;

        $recentMessages = $this->messages()
            ->orderBy('created_at')
            ->skip(max(0, $this->messages()->count() - $count))
            ->take($count)
            ->get();

        return $clone->setRelation('messages', $recentMessages);
    }

    public function getLastMessage(): ?Message
    {
        return $this->lastMessage;
    }

    public function getRecentMessages(int $count): Collection
    {
        return $this->messages()
            ->orderBy('created_at')
            ->skip(max(0, $this->messages()->count() - $count))
            ->take($count)
            ->get();
    }

    public function createMessage(MessageRole $role, string|View|null $content = null, array $metadata = []): Message
    {
        $normalizedContent = $this->normalizeContent($content);

        return $this->messages()->create([
            'role' => $role,
            'content' => $normalizedContent,
            'metadata' => $metadata,
            'status' => MessageStatus::Success,
            'is_complete' => filled($normalizedContent),
            'completed_at' => filled($normalizedContent) ? now() : null,
        ]);
    }

    public function createUserMessage(string|View $content, array $metadata = []): Message
    {
        return $this->createMessage(MessageRole::User, $content, $metadata);
    }

    public function createAssistantMessage(string|View $content, array $metadata = []): Message
    {
        return $this->createMessage(MessageRole::Assistant, $content, $metadata);
    }

    public function createSystemMessage(string|View $content, array $metadata = []): Message
    {
        return $this->createMessage(MessageRole::System, $content, $metadata);
    }

    public function createToolCallMessage(string|View $content, array $metadata = []): Message
    {
        return $this->createMessage(MessageRole::ToolCall, $content, $metadata);
    }

    public function createToolResultMessage(string|View $content, array $metadata = []): Message
    {
        return $this->createMessage(MessageRole::ToolResult, $content, $metadata);
    }

    // Conditional add helpers - fluent API (returns Conversation)

    public function addUserMessageIf($condition, string|View $content, array $metadata = []): self
    {
        return $this->when($condition, fn () => $this->addUserMessage($content, $metadata));
    }

    public function addUserMessageUnless($condition, string|View $content, array $metadata = []): self
    {
        return $this->unless($condition, fn () => $this->addUserMessage($content, $metadata));
    }

    public function addAssistantMessageIf($condition, string|View $content, array $metadata = []): self
    {
        return $this->when($condition, fn () => $this->addAssistantMessage($content, $metadata));
    }

    public function addAssistantMessageUnless($condition, string|View $content, array $metadata = []): self
    {
        return $this->unless($condition, fn () => $this->addAssistantMessage($content, $metadata));
    }

    public function addSystemMessageIf($condition, string|View $content, array $metadata = []): self
    {
        return $this->when($condition, fn () => $this->addSystemMessage($content, $metadata));
    }

    public function addSystemMessageUnless($condition, string|View $content, array $metadata = []): self
    {
        return $this->unless($condition, fn () => $this->addSystemMessage($content, $metadata));
    }

    public function addToolCallMessageIf($condition, string|View $content, array $metadata = []): self
    {
        return $this->when($condition, fn () => $this->addToolCallMessage($content, $metadata));
    }

    public function addToolCallMessageUnless($condition, string|View $content, array $metadata = []): self
    {
        return $this->unless($condition, fn () => $this->addToolCallMessage($content, $metadata));
    }

    public function addToolResultMessageIf($condition, string|View $content, array $metadata = []): self
    {
        return $this->when($condition, fn () => $this->addToolResultMessage($content, $metadata));
    }

    public function addToolResultMessageUnless($condition, string|View $content, array $metadata = []): self
    {
        return $this->unless($condition, fn () => $this->addToolResultMessage($content, $metadata));
    }

    // Conditional create helpers - direct API (returns Message or null)

    public function createUserMessageIf($condition, string|View $content, array $metadata = []): ?Message
    {
        return $condition ? $this->createUserMessage($content, $metadata) : null;
    }

    public function createUserMessageUnless($condition, string|View $content, array $metadata = []): ?Message
    {
        return ! $condition ? $this->createUserMessage($content, $metadata) : null;
    }

    public function createAssistantMessageIf($condition, string|View $content, array $metadata = []): ?Message
    {
        return $condition ? $this->createAssistantMessage($content, $metadata) : null;
    }

    public function createAssistantMessageUnless($condition, string|View $content, array $metadata = []): ?Message
    {
        return ! $condition ? $this->createAssistantMessage($content, $metadata) : null;
    }

    public function createSystemMessageIf($condition, string|View $content, array $metadata = []): ?Message
    {
        return $condition ? $this->createSystemMessage($content, $metadata) : null;
    }

    public function createSystemMessageUnless($condition, string|View $content, array $metadata = []): ?Message
    {
        return ! $condition ? $this->createSystemMessage($content, $metadata) : null;
    }

    public function createToolCallMessageIf($condition, string|View $content, array $metadata = []): ?Message
    {
        return $condition ? $this->createToolCallMessage($content, $metadata) : null;
    }

    public function createToolCallMessageUnless($condition, string|View $content, array $metadata = []): ?Message
    {
        return ! $condition ? $this->createToolCallMessage($content, $metadata) : null;
    }

    public function createToolResultMessageIf($condition, string|View $content, array $metadata = []): ?Message
    {
        return $condition ? $this->createToolResultMessage($content, $metadata) : null;
    }

    public function createToolResultMessageUnless($condition, string|View $content, array $metadata = []): ?Message
    {
        return ! $condition ? $this->createToolResultMessage($content, $metadata) : null;
    }
}
