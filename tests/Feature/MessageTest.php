<?php

use ElliottLawson\Converse\Models\Conversation;
use ElliottLawson\Converse\Models\Message;
use ElliottLawson\Converse\Models\MessageChunk;
use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Enums\MessageStatus;

it('has proper relationships', function () {
    $conversation = Conversation::factory()->create();
    $message = Message::factory()
        ->for($conversation)
        ->create();
    
    expect($message->conversation)->toBeInstanceOf(Conversation::class)
        ->and($message->conversation->id)->toBe($conversation->id);
});

it('can create message chunks', function () {
    $message = Message::factory()
        ->streaming()
        ->create();
    
    $chunk1 = $message->appendChunk('Hello ');
    $chunk2 = $message->appendChunk('world!');
    
    expect($message->chunks)->toHaveCount(2)
        ->and($chunk1)->toBeInstanceOf(MessageChunk::class)
        ->and($chunk1->sequence)->toBe(0)
        ->and($chunk2->sequence)->toBe(1);
});

it('updates content when appending chunks', function () {
    $message = Message::factory()
        ->streaming()
        ->create();
    
    expect($message->content)->toBe('');
    
    $message->appendChunk('Hello ');
    $message->refresh();
    expect($message->content)->toBe('Hello ');
    
    $message->appendChunk('world!');
    $message->refresh();
    expect($message->content)->toBe('Hello world!');
});

it('completes streaming correctly', function () {
    $message = Message::factory()
        ->streaming()
        ->create();
    
    $message->appendChunk('Test message');
    $message->completeStreaming(['tokens' => 10]);
    
    expect($message->is_complete)->toBeTrue()
        ->and($message->status)->toBe(MessageStatus::Success)
        ->and($message->completed_at)->not->toBeNull()
        ->and($message->metadata)->toHaveKey('tokens', 10);
});

it('handles failed streaming', function () {
    $message = Message::factory()
        ->streaming()
        ->create();
    
    $message->failStreaming('Error occurred');
    
    expect($message->is_complete)->toBeTrue()
        ->and($message->status)->toBe(MessageStatus::Error)
        ->and($message->completed_at)->not->toBeNull()
        ->and($message->metadata)->toHaveKey('error', 'Error occurred');
});