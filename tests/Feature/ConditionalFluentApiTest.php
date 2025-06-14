<?php

use ElliottLawson\Converse\Models\Conversation;

it('can use when method to add messages conditionally', function () {
    $conversation = Conversation::create();
    $isNewConversation = true;

    $conversation
        ->when($isNewConversation, function ($conversation) {
            $conversation->addSystemMessage('Welcome! This is your first conversation.');
        })
        ->addUserMessage('Hello')
        ->when($conversation->messages()->count() > 1, function ($conversation) {
            $conversation->addSystemMessage('Continuing conversation...');
        });

    expect($conversation->messages)->toHaveCount(3)
        ->and($conversation->messages->first()->content)->toBe('Welcome! This is your first conversation.')
        ->and($conversation->messages->last()->content)->toBe('Continuing conversation...');
});

it('can use unless method to skip messages conditionally', function () {
    $conversation = Conversation::create();
    $hasExistingMessages = false;

    $conversation
        ->unless($hasExistingMessages, function ($conversation) {
            $conversation->addSystemMessage('Starting new conversation');
        })
        ->addUserMessage('What is Laravel?')
        ->unless($conversation->messages()->count() > 5, function ($conversation) {
            $conversation->addAssistantMessage('Laravel is a PHP framework...');
        });

    expect($conversation->messages)->toHaveCount(3);
});

it('supports conditional with else callback', function () {
    $conversation = Conversation::create();
    $userPreferences = ['verbose' => true];

    $conversation
        ->addUserMessage('Tell me about PHP')
        ->when($userPreferences['verbose'] ?? false,
            function ($conversation) {
                $conversation->addSystemMessage('Provide detailed explanations');
            },
            function ($conversation) {
                $conversation->addSystemMessage('Keep responses concise');
            }
        );

    expect($conversation->messages->last()->content)->toBe('Provide detailed explanations');
});

it('handles conditional based on message count', function () {
    $conversation = Conversation::create();

    // First message - should trigger welcome
    $conversation
        ->when($conversation->messages()->count() === 0, function ($conv) {
            $conv->addSystemMessage('Welcome to your first conversation!');
        })
        ->addUserMessage('Hello');

    expect($conversation->messages()->count())->toBe(2);

    // Add more messages - should not trigger welcome again
    $conversation
        ->when($conversation->messages()->count() === 0, function ($conv) {
            $conv->addSystemMessage('Welcome to your first conversation!');
        })
        ->addUserMessage('Another message');

    expect($conversation->messages()->count())->toBe(3)
        ->and($conversation->messages()->where('content', 'Welcome to your first conversation!')->count())->toBe(1);
});

it('enables real world scenario context building', function () {
    $user = ['is_premium' => true, 'preferences' => ['language' => 'en']];
    $existingConversation = false;
    $context = 'technical_support';

    $conversation = Conversation::create(['context' => ['type' => $context]]);

    $conversation
        ->unless($existingConversation, function ($conversation) {
            $conversation->addSystemMessage('You are a helpful assistant.');
        })
        ->when($context === 'technical_support', function ($conversation) {
            $conversation->addSystemMessage('You specialize in technical support and debugging.');
        })
        ->when($user['is_premium'], function ($conversation) {
            $conversation->addSystemMessage('This is a premium user - provide priority support.');
        })
        ->unless($user['preferences']['language'] === 'en', function ($conversation) use ($user) {
            $conversation->addSystemMessage("Respond in {$user['preferences']['language']}");
        })
        ->addUserMessage('My application is showing errors');

    $systemMessages = $conversation->messages()->where('role', 'system')->get();
    expect($systemMessages)->toHaveCount(3);
});
