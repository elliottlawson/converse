# View Support

Converse seamlessly integrates with Laravel's view system, allowing you to pass Blade views directly to message methods without needing to call `->render()`. This feature makes managing complex prompts much easier by keeping them organized in dedicated Blade files rather than cluttering your PHP code with long strings.

## Basic Usage with Views

Instead of writing long prompts inline, you can now pass views directly:

```php
// Traditional approach with inline strings
$conversation->addSystemMessage('You are an AI assistant specialized in Laravel development...');

// New approach with views
$conversation->addSystemMessage(view('prompts.laravel-expert'));
```

The view file (`resources/views/prompts/laravel-expert.blade.php`):
```blade
You are an AI assistant specialized in Laravel development with deep knowledge of:
- Laravel's architecture and design patterns
- Best practices for application structure
- Performance optimization techniques
- Security considerations
- Testing strategies

Always provide code examples when relevant and explain the reasoning behind your recommendations.
```

## Passing Data to Views

You can pass data to views just like in any Laravel application:

```php
// Pass user preferences and context to the view
$conversation->addSystemMessage(
    view('prompts.personalized-assistant', [
        'userName' => $user->name,
        'expertise' => $user->expertise_level,
        'preferences' => $user->ai_preferences,
        'language' => $user->preferred_language,
    ])
);
```

The view file (`resources/views/prompts/personalized-assistant.blade.php`):
```blade
You are a helpful assistant for {{ $userName }}.

@if($expertise === 'beginner')
    Explain concepts in simple terms and avoid technical jargon.
@elseif($expertise === 'intermediate')
    Provide balanced explanations with some technical details.
@else
    Feel free to use advanced technical terminology and dive deep into implementations.
@endif

@if($preferences->get('examples'))
    Always include practical examples in your responses.
@endif

@if($language !== 'en')
    Please respond in {{ $language }}.
@endif
```

## Using Views for Complex Prompts

Views are particularly powerful for complex, multi-part prompts that would be unwieldy as strings:

```php
// Building a code review prompt with extensive guidelines
$conversation->addSystemMessage(
    view('prompts.code-reviewer', [
        'standards' => $project->coding_standards,
        'focusAreas' => ['security', 'performance', 'readability'],
        'severity' => 'strict',
    ])
);

// Adding user's code for review
$conversation->addUserMessage(
    view('prompts.review-request', [
        'code' => $codeToReview,
        'context' => $pullRequest->description,
        'files' => $pullRequest->changed_files,
    ])
);
```

The code reviewer prompt (`resources/views/prompts/code-reviewer.blade.php`):
```blade
You are an expert code reviewer. Please analyze code according to these guidelines:

## Coding Standards
@foreach($standards as $standard)
- {{ $standard->name }}: {{ $standard->description }}
@endforeach

## Focus Areas
@foreach($focusAreas as $area)
    @switch($area)
        @case('security')
            ### Security Review
            - Check for SQL injection vulnerabilities
            - Validate all user inputs
            - Ensure proper authentication and authorization
            - Look for exposed sensitive data
            @break
            
        @case('performance')
            ### Performance Review
            - Identify N+1 query problems
            - Check for inefficient loops
            - Review database indexing opportunities
            - Suggest caching strategies where appropriate
            @break
            
        @case('readability')
            ### Readability Review
            - Ensure clear variable and function names
            - Check for proper code organization
            - Verify adequate comments for complex logic
            - Suggest refactoring for overly complex methods
            @break
    @endswitch
@endforeach

Severity Level: {{ ucfirst($severity) }}
@if($severity === 'strict')
    Flag even minor issues and suggest improvements for all suboptimal patterns.
@endif
```

## More Examples

### Dynamic Context Building

Use views to build context dynamically based on your application state:

```php
// Building a customer support conversation with full context
$conversation->addSystemMessage(
    view('prompts.support-agent', [
        'customer' => $customer,
        'previousTickets' => $customer->tickets()->recent()->get(),
        'accountStatus' => $customer->subscription_status,
        'preferredLanguage' => $customer->language,
    ])
);
```

The view file (`resources/views/prompts/support-agent.blade.php`):
```blade
You are a customer support agent helping {{ $customer->name }}.

Customer Information:
- Account Type: {{ ucfirst($accountStatus) }}
- Member Since: {{ $customer->created_at->format('F Y') }}
- Preferred Language: {{ $preferredLanguage }}

@if($previousTickets->isNotEmpty())
Recent Support History:
@foreach($previousTickets as $ticket)
- {{ $ticket->created_at->format('M d') }}: {{ $ticket->subject }} ({{ $ticket->status }})
@endforeach
@endif

Provide helpful, empathetic support while considering their account history and status.
```

### Using Views with Conditional Logic

Views work seamlessly with Converse's conditional methods:

```php
$conversation
    ->addSystemMessageIf(
        $user->wants_examples,
        view('prompts.include-examples')
    )
    ->addSystemMessageUnless(
        $user->is_premium,
        view('prompts.limitations.free-tier')
    )
    ->addUserMessage($request->question);
```

This approach keeps your controller logic clean while managing complex conditional prompts through organized view files.

## Next Steps

- Explore [Conditional Logic](/guide/conditional-logic) to make your prompts dynamic
- Learn about [Message Types](/guide/messages) and when to use each
- Create your own view-based prompts for complex AI interactions 