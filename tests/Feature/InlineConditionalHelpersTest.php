<?php

use ElliottLawson\Converse\Models\Conversation;

it('can use addUserMessageIf to conditionally add messages', function () {
    $conversation = Conversation::create();
    $isPremiumUser = true;
    $isBasicUser = false;

    $conversation
        ->addUserMessageIf($isPremiumUser, 'Premium user message')
        ->addUserMessageIf($isBasicUser, 'Basic user message')
        ->addUserMessage('Regular message');

    expect($conversation->messages)->toHaveCount(2)
        ->and($conversation->messages->first()->content)->toBe('Premium user message')
        ->and($conversation->messages->last()->content)->toBe('Regular message');
});

it('can use addMessageUnless methods', function () {
    $conversation = Conversation::create();
    $hasSeenOnboarding = false;
    $isExpert = true;

    $conversation
        ->addSystemMessageUnless($hasSeenOnboarding, 'Welcome! Here is a quick guide...')
        ->addSystemMessageUnless($isExpert, 'Basic tips: ...')
        ->addUserMessage('Hello');

    expect($conversation->messages)->toHaveCount(2)
        ->and($conversation->messages->first()->content)->toBe('Welcome! Here is a quick guide...')
        ->and($conversation->messages->last()->content)->toBe('Hello');
});

it('supports all message types with if conditions', function () {
    $conversation = Conversation::create();

    $conversation
        ->addSystemMessageIf(true, 'System message')
        ->addUserMessageIf(true, 'User message')
        ->addAssistantMessageIf(true, 'Assistant message')
        ->addToolCallMessageIf(false, 'This should not appear')
        ->addToolResultMessageIf(true, 'Tool result');

    expect($conversation->messages)->toHaveCount(4);

    $messages = $conversation->messages;
    expect($messages->get(0)->content)->toBe('System message')
        ->and($messages->get(1)->content)->toBe('User message')
        ->and($messages->get(2)->content)->toBe('Assistant message')
        ->and($messages->get(3)->content)->toBe('Tool result');
});

it('can use createMessageIf to conditionally create messages', function () {
    $conversation = Conversation::create();

    $premiumMessage = $conversation->createUserMessageIf(true, 'Premium feature request');
    $basicMessage = $conversation->createUserMessageIf(false, 'Basic feature request');

    expect($premiumMessage)->not->toBeNull()
        ->and($premiumMessage->content)->toBe('Premium feature request')
        ->and($basicMessage)->toBeNull()
        ->and($conversation->messages)->toHaveCount(1);
});

it('can use createMessageUnless for inverse conditions', function () {
    $conversation = Conversation::create();
    $isTrialUser = true;

    $fullAccessMessage = $conversation->createSystemMessageUnless($isTrialUser, 'Full access granted');
    $trialMessage = $conversation->createSystemMessageUnless(! $isTrialUser, 'Trial limitations apply');

    expect($fullAccessMessage)->toBeNull()
        ->and($trialMessage)->not->toBeNull()
        ->and($trialMessage->content)->toBe('Trial limitations apply');
});

it('maintains fluent chaining with conditional helpers', function () {
    $conversation = Conversation::create();
    $userProfile = [
        'is_new' => true,
        'prefers_formal' => false,
        'language' => 'es',
    ];

    $result = $conversation
        ->addSystemMessageIf($userProfile['is_new'], 'Welcome to our platform!')
        ->addSystemMessageUnless($userProfile['prefers_formal'], 'Feel free to ask me anything!')
        ->addSystemMessageIf($userProfile['language'] !== 'en', 'Hablo español')
        ->addUserMessage('¿Cómo puedo empezar?');

    expect($result)->toBeInstanceOf(Conversation::class)
        ->and($conversation->messages)->toHaveCount(4);
});

it('handles metadata in conditional helpers', function () {
    $conversation = Conversation::create();
    $isPremium = true;

    $conversation->addUserMessageIf(
        $isPremium,
        'Request with metadata',
        ['priority' => 'high', 'features' => ['advanced']]
    );

    $message = $conversation->messages->first();
    expect($message->metadata)->toHaveKey('priority', 'high')
        ->and($message->metadata)->toHaveKey('features', ['advanced']);
});

it('demonstrates real world usage scenario', function () {
    $conversation = Conversation::create();

    // Simulate user context
    $user = [
        'first_time' => true,
        'subscription' => 'premium',
        'timezone' => 'America/New_York',
        'business_hours' => false,
    ];

    $conversation
        ->addSystemMessageIf($user['first_time'], 'Welcome! I\'m here to help you get started.')
        ->addSystemMessageIf($user['subscription'] === 'premium', 'Premium support is active.')
        ->addSystemMessageUnless($user['business_hours'], 'Note: You\'re contacting us outside business hours.')
        ->addUserMessage('I need help with deployment')
        ->addAssistantMessageIf(
            $user['subscription'] === 'premium',
            'I\'ll prioritize your deployment issue right away.'
        );

    expect($conversation->messages)->toHaveCount(5);

    // Verify the messages were added in the correct order
    $contents = $conversation->messages->pluck('content')->toArray();
    expect($contents)->toBe([
        'Welcome! I\'m here to help you get started.',
        'Premium support is active.',
        'Note: You\'re contacting us outside business hours.',
        'I need help with deployment',
        'I\'ll prioritize your deployment issue right away.',
    ]);
});

it('returns null for create methods when condition is false', function () {
    $conversation = Conversation::create();

    $nullResults = [
        $conversation->createUserMessageIf(false, 'User'),
        $conversation->createAssistantMessageIf(false, 'Assistant'),
        $conversation->createSystemMessageIf(false, 'System'),
        $conversation->createToolCallMessageIf(false, 'Tool'),
        $conversation->createToolResultMessageIf(false, 'Result'),
    ];

    foreach ($nullResults as $result) {
        expect($result)->toBeNull();
    }

    expect($conversation->messages)->toHaveCount(0);
});

it('supports complex conditional logic with closures', function () {
    $conversation = Conversation::create();
    $messageCount = 0;

    $checkMessageLimit = function () use (&$messageCount) {
        return $messageCount < 3;
    };

    // Add messages with dynamic condition
    for ($i = 0; $i < 5; $i++) {
        $conversation->addUserMessageIf($checkMessageLimit(), "Message {$i}");
        $messageCount++;
    }

    expect($conversation->messages)->toHaveCount(3)
        ->and($conversation->messages->last()->content)->toBe('Message 2');
});
