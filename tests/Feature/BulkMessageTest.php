<?php

use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Models\Conversation;

it('can add multiple messages as simple strings', function () {
    $conversation = Conversation::factory()->create();

    $messages = $conversation->addMessages([
        'Hello, I need help',
        'What seems to be the problem?',
        'My code is not working',
    ]);

    expect($messages)->toHaveCount(3)
        ->and($messages[0]->content)->toBe('Hello, I need help')
        ->and($messages[0]->role)->toBe(MessageRole::User)
        ->and($messages[1]->content)->toBe('What seems to be the problem?')
        ->and($messages[1]->role)->toBe(MessageRole::User)
        ->and($messages[2]->content)->toBe('My code is not working')
        ->and($messages[2]->role)->toBe(MessageRole::User);
});

it('can add messages with role and content', function () {
    $conversation = Conversation::factory()->create();

    $messages = $conversation->addMessages([
        ['role' => 'system', 'content' => 'You are a helpful assistant'],
        ['role' => 'user', 'content' => 'Hello!'],
        ['role' => 'assistant', 'content' => 'Hi there! How can I help?'],
    ]);

    expect($messages)->toHaveCount(3)
        ->and($messages[0]->role)->toBe(MessageRole::System)
        ->and($messages[1]->role)->toBe(MessageRole::User)
        ->and($messages[2]->role)->toBe(MessageRole::Assistant);
});

it('can add messages with type format', function () {
    $conversation = Conversation::factory()->create();

    $messages = $conversation->addMessages([
        ['type' => 'system', 'content' => 'Be concise'],
        ['type' => 'user', 'content' => 'What is Laravel?'],
        ['type' => 'assistant', 'content' => 'Laravel is a PHP framework'],
        ['type' => 'tool_call', 'content' => 'search_docs("laravel")'],
        ['type' => 'tool_result', 'content' => '{"found": 10}'],
    ]);

    expect($messages)->toHaveCount(5)
        ->and($messages[0]->role)->toBe(MessageRole::System)
        ->and($messages[1]->role)->toBe(MessageRole::User)
        ->and($messages[2]->role)->toBe(MessageRole::Assistant)
        ->and($messages[3]->role)->toBe(MessageRole::ToolCall)
        ->and($messages[4]->role)->toBe(MessageRole::ToolResult);
});

it('can add messages with metadata', function () {
    $conversation = Conversation::factory()->create();

    $messages = $conversation->addMessages([
        [
            'role' => 'user',
            'content' => 'Generate an image',
            'metadata' => ['timestamp' => '2024-01-01 10:00:00'],
        ],
        [
            'role' => 'assistant',
            'content' => 'Here is your image',
            'metadata' => ['model' => 'dall-e-3', 'cost' => 0.04],
        ],
    ]);

    expect($messages[0]->metadata)->toHaveKey('timestamp', '2024-01-01 10:00:00')
        ->and($messages[1]->metadata)->toHaveKey('model', 'dall-e-3')
        ->and($messages[1]->metadata)->toHaveKey('cost', 0.04);
});

it('can mix different message formats', function () {
    $conversation = Conversation::factory()->create();

    $messages = $conversation->addMessages([
        'Quick question',  // Simple string
        ['role' => 'assistant', 'content' => 'Sure, go ahead!'],  // Role format
        ['type' => 'user', 'content' => 'How do I...', 'metadata' => ['urgent' => true]],  // Type format with metadata
    ]);

    expect($messages)->toHaveCount(3)
        ->and($messages[0]->role)->toBe(MessageRole::User)
        ->and($messages[1]->role)->toBe(MessageRole::Assistant)
        ->and($messages[2]->role)->toBe(MessageRole::User)
        ->and($messages[2]->metadata)->toHaveKey('urgent', true);
});

it('throws exception for unknown message type', function () {
    $conversation = Conversation::factory()->create();

    $conversation->addMessages([
        ['type' => 'unknown', 'content' => 'Test'],
    ]);
})->throws(\InvalidArgumentException::class, 'Unknown message type: unknown');

it('can handle enum instances in role field', function () {
    $conversation = Conversation::factory()->create();

    $messages = $conversation->addMessages([
        ['role' => MessageRole::User, 'content' => 'Using enum directly'],
        ['role' => 'assistant', 'content' => 'Using string'],
    ]);

    expect($messages[0]->role)->toBe(MessageRole::User)
        ->and($messages[1]->role)->toBe(MessageRole::Assistant);
});

it('can add messages using variadic parameters', function () {
    $conversation = Conversation::factory()->create();

    $messages = $conversation->addMessages(
        'First message',
        'Second message',
        'Third message'
    );

    expect($messages)->toHaveCount(3)
        ->and($messages[0]->content)->toBe('First message')
        ->and($messages[0]->role)->toBe(MessageRole::User)
        ->and($messages[1]->content)->toBe('Second message')
        ->and($messages[1]->role)->toBe(MessageRole::User)
        ->and($messages[2]->content)->toBe('Third message')
        ->and($messages[2]->role)->toBe(MessageRole::User);
});
