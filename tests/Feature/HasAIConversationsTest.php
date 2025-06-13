<?php

use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Models\Conversation;
use ElliottLawson\Converse\Tests\Models\TestUser;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Create users table for testing
    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('users');
});

it('can start a conversation for a model', function () {
    $user = TestUser::create(['name' => 'John Doe', 'email' => 'john@example.com']);

    $conversation = $user->startConversation(['title' => 'Support Chat']);

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->title)->toBe('Support Chat')
        ->and($conversation->conversable_type)->toBe(TestUser::class)
        ->and($conversation->conversable_id)->toBe($user->id);
});

it('can retrieve all conversations for a model', function () {
    $user = TestUser::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $user->startConversation(['title' => 'Chat 1']);
    $user->startConversation(['title' => 'Chat 2']);

    $conversations = $user->conversations;

    expect($conversations)->toHaveCount(2)
        ->and($conversations->pluck('title')->toArray())->toBe(['Chat 1', 'Chat 2']);
});

it('can continue existing conversation', function () {
    $user = TestUser::create(['name' => 'Charlie', 'email' => 'charlie@example.com']);

    $conversation = $user->startConversation(['title' => 'Ongoing Chat']);
    $conversationId = $conversation->id;

    $continued = $user->continueConversation($conversationId);

    expect($continued)->toBeInstanceOf(Conversation::class)
        ->and($continued->id)->toBe($conversation->id);
});

it('can get only active conversations', function () {
    $user = TestUser::create(['name' => 'Dave', 'email' => 'dave@example.com']);

    $active1 = $user->startConversation(['title' => 'Active 1']);
    $active2 = $user->startConversation(['title' => 'Active 2']);
    $deleted = $user->startConversation(['title' => 'Deleted']);

    $deleted->delete(); // Soft delete

    $activeConversations = $user->activeConversations;

    expect($activeConversations)->toHaveCount(2)
        ->and($activeConversations->pluck('title')->toArray())->toBe(['Active 1', 'Active 2']);
});

it('can create a conversation and add messages', function () {
    $user = TestUser::create(['name' => 'Eve', 'email' => 'eve@example.com']);

    $conversation = $user->startConversation(['title' => 'AI Chat']);

    $userMessage = $conversation->addUserMessage('Hello AI!');
    $assistantMessage = $conversation->addAssistantMessage('Hello! How can I help you?');

    expect($conversation->messages)->toHaveCount(2)
        ->and($userMessage->role)->toBe(MessageRole::User)
        ->and($userMessage->content)->toBe('Hello AI!')
        ->and($assistantMessage->role)->toBe(MessageRole::Assistant)
        ->and($assistantMessage->content)->toBe('Hello! How can I help you?');
});

it('maintains relationship after retrieving conversation', function () {
    $user = TestUser::create(['name' => 'Frank', 'email' => 'frank@example.com']);

    $conversation = $user->startConversation(['title' => 'Test']);

    // Retrieve conversation through relationship
    $retrieved = $user->conversations()->first();

    expect($retrieved->conversable)->toBeInstanceOf(TestUser::class)
        ->and($retrieved->conversable->id)->toBe($user->id)
        ->and($retrieved->conversable->name)->toBe('Frank');
});

it('can have multiple models with conversations', function () {
    $user1 = TestUser::create(['name' => 'User 1', 'email' => 'user1@example.com']);
    $user2 = TestUser::create(['name' => 'User 2', 'email' => 'user2@example.com']);

    $user1Conv = $user1->startConversation(['title' => 'User 1 Chat']);
    $user2Conv = $user2->startConversation(['title' => 'User 2 Chat']);

    expect($user1->conversations)->toHaveCount(1)
        ->and($user2->conversations)->toHaveCount(1)
        ->and($user1Conv->conversable_id)->toBe($user1->id)
        ->and($user2Conv->conversable_id)->toBe($user2->id);
});
