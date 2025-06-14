<?php

use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Models\Conversation;

it('can add user message using helper method', function () {
    $conversation = Conversation::factory()->create();

    $conversation->addUserMessage('Hello from user');
    $message = $conversation->getLastMessage();

    expect($message->role)->toBe(MessageRole::User)
        ->and($message->content)->toBe('Hello from user')
        ->and($message->is_complete)->toBeTrue();
});

it('can add assistant message using helper method', function () {
    $conversation = Conversation::factory()->create();

    $conversation->addAssistantMessage('Hello from assistant');
    $message = $conversation->getLastMessage();

    expect($message->role)->toBe(MessageRole::Assistant)
        ->and($message->content)->toBe('Hello from assistant')
        ->and($message->is_complete)->toBeTrue();
});

it('can add system message using helper method', function () {
    $conversation = Conversation::factory()->create();

    $conversation->addSystemMessage('You are a helpful assistant');
    $message = $conversation->getLastMessage();

    expect($message->role)->toBe(MessageRole::System)
        ->and($message->content)->toBe('You are a helpful assistant')
        ->and($message->is_complete)->toBeTrue();
});

it('can add tool call message using helper method', function () {
    $conversation = Conversation::factory()->create();

    $conversation->addToolCallMessage('Calling weather API');
    $message = $conversation->getLastMessage();

    expect($message->role)->toBe(MessageRole::ToolCall)
        ->and($message->content)->toBe('Calling weather API')
        ->and($message->is_complete)->toBeTrue();
});

it('can add tool result message using helper method', function () {
    $conversation = Conversation::factory()->create();

    $conversation->addToolResultMessage('Weather: Sunny, 72°F');
    $message = $conversation->getLastMessage();

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

it('can chain add message methods for fluent API', function () {
    $conversation = Conversation::factory()->create();

    $result = $conversation
        ->addSystemMessage('You are a Laravel developer...')
        ->addUserMessage('Implementation Context: Product and technical requirements...')
        ->addUserMessage('Phase Context: This is phase 1 setup...')
        ->addUserMessage('Deliverable: Please implement user authentication...');

    expect($result)->toBeInstanceOf(Conversation::class)
        ->and($conversation->messages()->count())->toBe(4)
        ->and($conversation->messages()->system()->count())->toBe(1)
        ->and($conversation->messages()->user()->count())->toBe(3);
});

it('can use selectRecentMessages helper', function () {
    $conversation = Conversation::factory()->create();
    
    $conversation->addUserMessage('Message 1');
    $conversation->addUserMessage('Message 2');
    $conversation->addUserMessage('Message 3');

    $subset = $conversation->selectRecentMessages(2);
    
    expect($subset->messages)->toHaveCount(2)
        ->and($subset->messages->first()->content)->toBe('Message 2')
        ->and($subset->messages->last()->content)->toBe('Message 3');
});

it('can use getRecentMessages helper', function () {
    $conversation = Conversation::factory()->create();
    
    $conversation->addUserMessage('Message 1');
    $conversation->addUserMessage('Message 2');
    $conversation->addUserMessage('Message 3');

    $messages = $conversation->getRecentMessages(2);
    
    expect($messages)->toHaveCount(2)
        ->and($messages->first()->content)->toBe('Message 2')
        ->and($messages->last()->content)->toBe('Message 3');
});
