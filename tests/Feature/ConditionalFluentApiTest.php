<?php

namespace ElliottLawson\Converse\Tests\Feature;

use ElliottLawson\Converse\Models\Conversation;
use ElliottLawson\Converse\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConditionalFluentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_method_adds_messages_conditionally()
    {
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

        $this->assertCount(3, $conversation->messages);
        $this->assertEquals('Welcome! This is your first conversation.', $conversation->messages->first()->content);
        $this->assertEquals('Continuing conversation...', $conversation->messages->last()->content);
    }

    public function test_unless_method_skips_messages_conditionally()
    {
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

        $this->assertCount(3, $conversation->messages);
    }

    public function test_conditional_with_callback_parameters()
    {
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

        $messages = $conversation->messages;
        $this->assertEquals('Provide detailed explanations', $messages->last()->content);
    }

    public function test_conditional_based_on_message_count()
    {
        $conversation = Conversation::create();

        // First message - should trigger welcome
        $conversation
            ->when($conversation->messages()->count() === 0, function ($conv) {
                $conv->addSystemMessage('Welcome to your first conversation!');
            })
            ->addUserMessage('Hello');

        $this->assertEquals(2, $conversation->messages()->count());

        // Add more messages - should not trigger welcome again
        $conversation
            ->when($conversation->messages()->count() === 0, function ($conv) {
                $conv->addSystemMessage('Welcome to your first conversation!');
            })
            ->addUserMessage('Another message');

        $this->assertEquals(3, $conversation->messages()->count());
        $this->assertEquals(1, $conversation->messages()->where('content', 'Welcome to your first conversation!')->count());
    }

    public function test_real_world_scenario_context_building()
    {
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
        $this->assertCount(3, $systemMessages);
    }
}
