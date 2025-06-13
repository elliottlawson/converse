<?php

use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Events\ChunkReceived;
use ElliottLawson\Converse\Events\ConversationCreated;
use ElliottLawson\Converse\Events\MessageCompleted;
use ElliottLawson\Converse\Events\MessageCreated;
use ElliottLawson\Converse\Models\Conversation;
use Illuminate\Support\Facades\Event;

it('dispatches conversation created event', function () {
    Event::fake();

    $conversation = Conversation::create(['title' => 'Test']);

    Event::assertDispatched(ConversationCreated::class, function ($event) use ($conversation) {
        return $event->conversation->id === $conversation->id;
    });
});

it('dispatches message created event', function () {
    Event::fake();

    $conversation = Conversation::create(['title' => 'Test']);
    $message = $conversation->addMessage(MessageRole::User, 'Hello');

    Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
        return $event->message->id === $message->id;
    });
});

it('dispatches chunk received event', function () {
    Event::fake();

    $conversation = Conversation::create(['title' => 'Test']);
    $message = $conversation->startStreamingMessage(MessageRole::Assistant);

    $message->appendChunk('Test chunk');

    Event::assertDispatched(ChunkReceived::class, function ($event) {
        return $event->chunk->content === 'Test chunk';
    });
});

it('dispatches message completed event', function () {
    Event::fake();

    $conversation = Conversation::create(['title' => 'Test']);
    $message = $conversation->startStreamingMessage(MessageRole::Assistant);

    $message->completeStreaming();

    Event::assertDispatched(MessageCompleted::class, function ($event) use ($message) {
        return $event->message->id === $message->id;
    });
});
