<?php

namespace ElliottLawson\Converse\Messages;

use ElliottLawson\Converse\Enums\MessageRole;

class ToolCallMessage
{
    public function __construct(
        public readonly string $content,
        public readonly array $metadata = []
    ) {}
    
    public function getRole(): MessageRole
    {
        return MessageRole::ToolCall;
    }
    
    public function toArray(): array
    {
        return [
            'role' => $this->getRole(),
            'content' => $this->content,
            'metadata' => $this->metadata,
        ];
    }
}