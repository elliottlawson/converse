<?php

use ElliottLawson\Converse\Models\Conversation;
use ElliottLawson\Converse\Messages\UserMessage;
use ElliottLawson\Converse\Messages\AssistantMessage;
use ElliottLawson\Converse\Messages\SystemMessage;
use ElliottLawson\Converse\Messages\ToolCallMessage;
use ElliottLawson\Converse\Messages\ToolResultMessage;
use ElliottLawson\Converse\Enums\MessageRole;

it('can add messages using DTOs', function () {
    $conversation = Conversation::factory()->create();
    
    $messages = $conversation->addMessages([
        new SystemMessage('You are a helpful assistant'),
        new UserMessage('Hello!'),
        new AssistantMessage('Hi there! How can I help?'),
    ]);
    
    expect($messages)->toHaveCount(3)
        ->and($messages[0]->role)->toBe(MessageRole::System)
        ->and($messages[0]->content)->toBe('You are a helpful assistant')
        ->and($messages[1]->role)->toBe(MessageRole::User)
        ->and($messages[1]->content)->toBe('Hello!')
        ->and($messages[2]->role)->toBe(MessageRole::Assistant)
        ->and($messages[2]->content)->toBe('Hi there! How can I help?');
});

it('can add messages with metadata using DTOs', function () {
    $conversation = Conversation::factory()->create();
    
    $messages = $conversation->addMessages([
        new UserMessage('Generate an image', ['request_id' => '123']),
        new AssistantMessage('Here is your image', ['model' => 'dall-e-3', 'cost' => 0.04]),
    ]);
    
    expect($messages[0]->metadata)->toHaveKey('request_id', '123')
        ->and($messages[1]->metadata)->toHaveKey('model', 'dall-e-3')
        ->and($messages[1]->metadata)->toHaveKey('cost', 0.04);
});

it('can add tool messages using DTOs', function () {
    $conversation = Conversation::factory()->create();
    
    $messages = $conversation->addMessages([
        new ToolCallMessage('search_web("Laravel documentation")', ['call_id' => 'call_123']),
        new ToolResultMessage('{"results": ["Laravel.com", "Laracasts.com"]}', ['call_id' => 'call_123']),
    ]);
    
    expect($messages[0]->role)->toBe(MessageRole::ToolCall)
        ->and($messages[1]->role)->toBe(MessageRole::ToolResult)
        ->and($messages[0]->metadata)->toHaveKey('call_id', 'call_123');
});

it('can mix DTOs with other formats', function () {
    $conversation = Conversation::factory()->create();
    
    $messages = $conversation->addMessages([
        new SystemMessage('Be helpful'),
        'Quick question',  // String
        ['role' => 'assistant', 'content' => 'Sure!'],  // Array
        new UserMessage('Thanks!'),
    ]);
    
    expect($messages)->toHaveCount(4)
        ->and($messages[0]->role)->toBe(MessageRole::System)
        ->and($messages[1]->role)->toBe(MessageRole::User)
        ->and($messages[2]->role)->toBe(MessageRole::Assistant)
        ->and($messages[3]->role)->toBe(MessageRole::User);
});

it('can use DTOs with variadic parameters', function () {
    $conversation = Conversation::factory()->create();
    
    $messages = $conversation->addMessages(
        new UserMessage('First question'),
        new AssistantMessage('First answer'),
        new UserMessage('Follow up')
    );
    
    expect($messages)->toHaveCount(3)
        ->and($messages[0]->content)->toBe('First question')
        ->and($messages[1]->content)->toBe('First answer')
        ->and($messages[2]->content)->toBe('Follow up');
});

it('throws exception for unknown object types', function () {
    $conversation = Conversation::factory()->create();
    
    $conversation->addMessages([
        new stdClass(),
    ]);
})->throws(\InvalidArgumentException::class, 'Unknown message object type: stdClass');