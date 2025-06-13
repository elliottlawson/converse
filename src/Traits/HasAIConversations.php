<?php

namespace ElliottLawson\Converse\Traits;

use ElliottLawson\Converse\Models\Conversation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAIConversations
{
    public function conversations(): MorphMany
    {
        return $this->morphMany(Conversation::class, 'conversable');
    }
    
    public function startConversation(array $attributes = []): Conversation
    {
        return $this->conversations()->create($attributes);
    }
    
    public function continueConversation(string $conversationId): Conversation
    {
        return $this->conversations()->findOrFail($conversationId);
    }
    
    public function activeConversations(): MorphMany
    {
        return $this->conversations()->whereNull('deleted_at');
    }
}