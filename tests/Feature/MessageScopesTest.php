<?php

use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Models\Conversation;
use ElliottLawson\Converse\Models\Message;

it('can filter messages by role using scopes', function () {
    $conversation = Conversation::factory()->create();

    // Create messages of different roles
    $conversation->addUserMessage('User question');
    $conversation->addAssistantMessage('Assistant response');
    $conversation->addSystemMessage('System prompt');

    expect($conversation->messages()->user()->count())->toBe(1)
        ->and($conversation->messages()->assistant()->count())->toBe(1)
        ->and($conversation->messages()->system()->count())->toBe(1);
});

it('can filter completed and streaming messages', function () {
    $conversation = Conversation::factory()->create();

    // Create completed messages
    $conversation->addUserMessage('First message');
    $conversation->addAssistantMessage('Response');

    // Create streaming message
    $streaming = $conversation->startStreamingAssistant();

    expect($conversation->messages()->completed()->count())->toBe(2)
        ->and($conversation->messages()->streaming()->count())->toBe(1)
        ->and($conversation->messages()->streaming()->first()->id)->toBe($streaming->id);
});

it('can filter failed messages', function () {
    $conversation = Conversation::factory()->create();

    // Create successful message
    $conversation->addUserMessage('Test');

    // Create failed streaming message
    $failed = $conversation->startStreamingAssistant();
    $failed->failStreaming('Network error');

    expect($conversation->messages()->failed()->count())->toBe(1)
        ->and($conversation->messages()->failed()->first()->metadata['error'])->toBe('Network error');
});

it('can combine scopes', function () {
    $conversation = Conversation::factory()->create();

    // Create various messages
    $conversation->addUserMessage('User 1');
    $conversation->addUserMessage('User 2');
    $conversation->addAssistantMessage('Assistant 1');

    // Create streaming user message that fails
    $failedUser = $conversation->startStreamingUser();
    $failedUser->failStreaming('User error');

    // Create streaming assistant message that fails
    $failedAssistant = $conversation->startStreamingAssistant();
    $failedAssistant->failStreaming('Assistant error');

    expect($conversation->messages()->user()->count())->toBe(3)
        ->and($conversation->messages()->user()->completed()->count())->toBe(3) // All are marked complete
        ->and($conversation->messages()->user()->failed()->count())->toBe(1)
        ->and($conversation->messages()->failed()->count())->toBe(2);
});

it('can use byRole scope with enum', function () {
    $conversation = Conversation::factory()->create();

    $conversation->addUserMessage('User');
    $conversation->addAssistantMessage('Assistant');
    $conversation->addToolCallMessage('Tool call');
    $conversation->addToolResultMessage('Tool result');

    expect($conversation->messages()->byRole(MessageRole::User)->count())->toBe(1)
        ->and($conversation->messages()->byRole(MessageRole::Assistant)->count())->toBe(1)
        ->and($conversation->messages()->byRole(MessageRole::ToolCall)->count())->toBe(1)
        ->and($conversation->messages()->byRole(MessageRole::ToolResult)->count())->toBe(1);
});
