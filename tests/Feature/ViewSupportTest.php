<?php

use ElliottLawson\Converse\Models\Conversation;
use Illuminate\Support\Facades\View;

beforeEach(function () {
    // Create test views
    View::addLocation(__DIR__.'/../fixtures/views');
});

it('can add messages using view objects', function () {
    $conversation = Conversation::create();

    // Create a mock view
    $view = View::make('test-prompt', ['name' => 'Laravel']);

    $conversation->addSystemMessage($view);

    $message = $conversation->messages->first();
    expect($message->content)->toContain('Laravel');
});

it('supports views in all message types', function () {
    $conversation = Conversation::create();

    $systemView = View::make('system-prompt');
    $userView = View::make('user-prompt');
    $assistantView = View::make('assistant-prompt');

    $conversation
        ->addSystemMessage($systemView)
        ->addUserMessage($userView)
        ->addAssistantMessage($assistantView);

    expect($conversation->messages)->toHaveCount(3);
});

it('can mix views and strings', function () {
    $conversation = Conversation::create();

    $view = View::make('test-prompt', ['name' => 'World']);

    $conversation
        ->addSystemMessage('Plain text message')
        ->addSystemMessage($view)
        ->addUserMessage('Another plain text');

    expect($conversation->messages)->toHaveCount(3)
        ->and($conversation->messages->get(0)->content)->toBe('Plain text message')
        ->and($conversation->messages->get(1)->content)->toContain('World');
});

it('works with create methods returning message objects', function () {
    $conversation = Conversation::create();

    $view = View::make('test-prompt', ['name' => 'Test']);

    $message = $conversation->createUserMessage($view);

    expect($message)->toBeInstanceOf(\ElliottLawson\Converse\Models\Message::class)
        ->and($message->content)->toContain('Test');
});

it('supports views in conditional helpers', function () {
    $conversation = Conversation::create();
    $isPremium = true;

    $premiumView = View::make('premium-prompt');

    $conversation->addSystemMessageIf($isPremium, $premiumView);

    expect($conversation->messages)->toHaveCount(1);
});

it('handles null content properly', function () {
    $conversation = Conversation::create();

    $conversation->addMessage(\ElliottLawson\Converse\Enums\MessageRole::User, null);

    $message = $conversation->messages->first();
    expect($message->content)->toBeNull()
        ->and($message->is_complete)->toBeFalse();
});

it('demonstrates real world usage with complex prompts', function () {
    $conversation = Conversation::create();

    // User context
    $user = [
        'name' => 'John Doe',
        'subscription' => 'premium',
        'expertise' => 'intermediate',
        'preferences' => ['verbose' => true, 'examples' => true],
    ];

    // Product context
    $product = [
        'name' => 'Laravel App',
        'type' => 'SaaS',
        'stage' => 'MVP',
    ];

    // Use views for complex prompts
    $systemPrompt = View::make('prompts.system', compact('user'));
    $contextPrompt = View::make('prompts.product-context', compact('product'));

    $conversation
        ->addSystemMessage($systemPrompt)
        ->addSystemMessage($contextPrompt)
        ->addUserMessage('How should I structure my authentication?');

    expect($conversation->messages)->toHaveCount(3)
        ->and($conversation->messages->get(0)->content)->toContain('John Doe')
        ->and($conversation->messages->get(1)->content)->toContain('Laravel App');
});

it('properly handles view data', function () {
    $conversation = Conversation::create();

    // Test with view data
    $data = [
        'instructions' => ['Be helpful', 'Be concise', 'Use examples'],
        'context' => 'technical support',
        'priority' => 'high',
    ];

    $view = View::make('complex-prompt', $data);

    $conversation->addSystemMessage($view);

    $content = $conversation->messages->first()->content;
    expect($content)->toContain('Be helpful')
        ->and($content)->toContain('technical support')
        ->and($content)->toContain('high');
});

// Test with actual blade syntax
it('renders blade directives properly', function () {
    $conversation = Conversation::create();

    $view = \Illuminate\Support\Facades\Blade::render(
        'You are helping {{ $name }} with {{ $task }}',
        ['name' => 'Alice', 'task' => 'Laravel development']
    );

    $conversation->addSystemMessage($view);

    expect($conversation->messages->first()->content)
        ->toBe('You are helping Alice with Laravel development');
});

it('works with inline blade templates', function () {
    $conversation = Conversation::create();

    $requirements = ['Fast performance', 'Clean code', 'Good documentation'];

    $view = \Illuminate\Support\Facades\Blade::render(
        '@foreach($requirements as $req)- {{ $req }}
@endforeach',
        compact('requirements')
    );

    $conversation->addSystemMessage($view);

    $content = $conversation->messages->first()->content;
    expect($content)->toContain('- Fast performance')
        ->and($content)->toContain('- Clean code')
        ->and($content)->toContain('- Good documentation');
});
