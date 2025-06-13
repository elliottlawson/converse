<?php

namespace ElliottLawson\Converse\Contracts;

use ElliottLawson\Converse\Models\Conversation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Conversable
{
    public function conversations(): MorphMany;

    public function startConversation(array $attributes = []): Conversation;

    public function findConversation(string $uuid): ?Conversation;

    public function continueConversation(string $conversationId): Conversation;
}
