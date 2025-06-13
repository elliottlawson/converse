<?php

use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Models\Conversation;

it('can add user message using helper method', function () {
    $conversation = Conversation::factory()->create();

    $message = $conversation->addUserMessage('Hello from user');

    expect($message->role)->toBe(MessageRole::User)
        ->and($message->content)->toBe('Hello from user')
        ->and($message->is_complete)->toBeTrue();
});

it('can add assistant message using helper method', function () {
    $conversation = Conversation::factory()->create();

    $message = $conversation->addAssistantMessage('Hello from assistant');

    expect($message->role)->toBe(MessageRole::Assistant)
        ->and($message->content)->toBe('Hello from assistant')
        ->and($message->is_complete)->toBeTrue();
});

it('can add system message using helper method', function () {
    $conversation = Conversation::factory()->create();

    $message = $conversation->addSystemMessage('You are a helpful assistant');

    expect($message->role)->toBe(MessageRole::System)
        ->and($message->content)->toBe('You are a helpful assistant')
        ->and($message->is_complete)->toBeTrue();
});

it('can add tool call message using helper method', function () {
    $conversation = Conversation::factory()->create();

    $message = $conversation->addToolCallMessage('Calling weather API');

    expect($message->role)->toBe(MessageRole::ToolCall)
        ->and($message->content)->toBe('Calling weather API')
        ->and($message->is_complete)->toBeTrue();
});

it('can add tool result message using helper method', function () {
    $conversation = Conversation::factory()->create();

    $message = $conversation->addToolResultMessage('Weather: Sunny, 72°F');

    expect($message->role)->toBe(MessageRole::ToolResult)
        ->and($message->content)->toBe('Weather: Sunny, 72°F')
        ->and($message->is_complete)->toBeTrue();
});

it('can start streaming assistant using helper method', function () {
    $conversation = Conversation::factory()->create();

    $message = $conversation->startStreamingAssistant(['model' => 'gpt-4']);

    expect($message->role)->toBe(MessageRole::Assistant)
        ->and($message->content)->toBe('')
        ->and($message->is_complete)->toBeFalse()
        ->and($message->metadata)->toHaveKey('streamed', true)
        ->and($message->metadata)->toHaveKey('model', 'gpt-4');
});

it('can start streaming user using helper method', function () {
    $conversation = Conversation::factory()->create();

    $message = $conversation->startStreamingUser();

    expect($message->role)->toBe(MessageRole::User)
        ->and($message->content)->toBe('')
        ->and($message->is_complete)->toBeFalse()
        ->and($message->metadata)->toHaveKey('streamed', true);
});
