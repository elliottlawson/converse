<?php

use ElliottLawson\Converse\Models\Conversation;
use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Enums\MessageStatus;

it('can create conversation', function () {
    $conversation = Conversation::factory()
        ->withTitle('Test Conversation')
        ->withMetadata(['provider' => 'test'])
        ->create();
    
    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->title)->toBe('Test Conversation')
        ->and($conversation->uuid)->not->toBeNull();
});

it('can add message to conversation', function () {
    $conversation = Conversation::factory()->create();
    
    $message = $conversation->addMessage(
        MessageRole::User,
        'Hello, world!',
        ['test' => true]
    );
    
    expect($message->role)->toBe(MessageRole::User)
        ->and($message->content)->toBe('Hello, world!')
        ->and($message->status)->toBe(MessageStatus::Success)
        ->and($message->is_complete)->toBeTrue();
});

it('can handle streaming messages', function () {
    $conversation = Conversation::factory()->create();
    
    $message = $conversation->startStreamingMessage(MessageRole::Assistant);
    
    expect($message->role)->toBe(MessageRole::Assistant)
        ->and($message->content)->toBe('')
        ->and($message->status)->toBe(MessageStatus::Pending)
        ->and($message->is_complete)->toBeFalse();
    
    $message->appendChunk('Hello ');
    $message->appendChunk('world!');
    
    $message->completeStreaming(['tokens' => 2]);
    
    $message->refresh();
    
    expect($message->content)->toBe('Hello world!')
        ->and($message->status)->toBe(MessageStatus::Success)
        ->and($message->is_complete)->toBeTrue()
        ->and($message->chunks()->count())->toBe(2);
});

it('soft delete cascades to messages', function () {
    $conversation = Conversation::factory()->create();
    $message = $conversation->addMessage(MessageRole::User, 'Test message');
    
    $conversation->delete();
    
    expect($conversation->trashed())->toBeTrue()
        ->and($message->fresh()->trashed())->toBeTrue();
    
    $conversation->restore();
    
    expect($conversation->trashed())->toBeFalse()
        ->and($message->fresh()->trashed())->toBeFalse();
});