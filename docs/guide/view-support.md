# View Support

Laravel Converse seamlessly integrates with Laravel's view system, allowing you to pass Blade views directly to message methods without needing to call `->render()`. This feature makes managing complex prompts much easier by keeping them organized in dedicated Blade files rather than cluttering your PHP code with long strings.

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

## Mixing Views and Strings

You can freely mix views with regular strings in your conversation flow:

```php
$conversation
    ->addSystemMessage(view('prompts.base-assistant'))
    ->addSystemMessage('Additionally, today is ' . now()->format('l, F j, Y'))
    ->addUserMessage(view('templates.question', ['topic' => $request->topic]))
    ->when($includeContext, function ($conversation) use ($context) {
        $conversation->addSystemMessage(view('prompts.context', compact('context')));
    })
    ->addUserMessage($request->question);
```

## Using Views with Conditional Helpers

Views work seamlessly with the conditional helper methods:

```php
$conversation
    ->addMessageIf(
        $user->wants_examples,
        'system',
        view('prompts.include-examples')
    )
    ->addMessageUnless(
        $user->is_premium,
        'system',
        view('prompts.limitations.free-tier')
    )
    ->when($user->has_custom_instructions, function ($conversation) use ($user) {
        $conversation->addSystemMessage(
            view('prompts.custom-instructions', [
                'instructions' => $user->custom_instructions
            ])
        );
    });
```

## Benefits of Using Views for Prompts

1. **Organization**: Keep prompts organized in a dedicated directory structure
2. **Reusability**: Share common prompts across different parts of your application
3. **Maintainability**: Update prompts without touching controller logic
4. **Version Control**: Track prompt changes separately from code changes
5. **Blade Features**: Leverage all Blade features like includes, components, and layouts
6. **Syntax Highlighting**: Get proper syntax highlighting in your IDE for complex prompts
7. **Testing**: Easily test prompt generation with different data inputs

## Recommended Directory Structure

Here's a suggested way to organize your prompt views:

```
resources/views/prompts/
├── agents/
│   ├── customer-support.blade.php
│   ├── technical-expert.blade.php
│   └── sales-assistant.blade.php
├── contexts/
│   ├── user-history.blade.php
│   ├── session-data.blade.php
│   └── business-rules.blade.php
├── templates/
│   ├── question.blade.php
│   ├── analysis-request.blade.php
│   └── summary-request.blade.php
└── shared/
    ├── base-instructions.blade.php
    └── safety-guidelines.blade.php
```

## Advanced Usage

### Using Blade Components

You can create reusable Blade components for common prompt patterns:

```blade
{{-- resources/views/components/prompt-section.blade.php --}}
<div class="prompt-section">
    <h3>{{ $title }}</h3>
    {{ $slot }}
</div>
```

```blade
{{-- resources/views/prompts/analysis.blade.php --}}
<x-prompt-section title="Analysis Guidelines">
    Analyze the provided data focusing on:
    - Accuracy and completeness
    - Potential issues or anomalies
    - Actionable recommendations
</x-prompt-section>
```

### Using Blade Includes

Share common instructions across multiple prompts:

```blade
{{-- resources/views/prompts/shared/formatting.blade.php --}}
Format all responses using:
- Clear headings for different sections
- Bullet points for lists
- Code blocks with syntax highlighting
- Tables for structured data comparison
```

```blade
{{-- In your main prompt --}}
You are a technical documentation assistant.

@include('prompts.shared.formatting')

Focus on creating comprehensive yet concise documentation.
```

## Best Practices

1. **Keep prompts focused**: Each view should represent a single, cohesive prompt or prompt section
2. **Use meaningful names**: Name your view files descriptively (e.g., `code-review-strict.blade.php` not `prompt1.blade.php`)
3. **Parameterize variations**: Use view data to handle variations rather than creating many similar files
4. **Document parameters**: Add comments in your views explaining expected parameters
5. **Test your prompts**: Create tests that verify prompts render correctly with different data

```blade
{{-- 
    Personalized Assistant Prompt
    
    Expected parameters:
    - $userName (string): The user's display name
    - $expertise (string): User's expertise level (beginner|intermediate|expert)
    - $preferences (Collection): User's AI preferences
    - $language (string): User's preferred language code
--}}
You are a helpful assistant for {{ $userName }}.
...
```

This view support feature transforms how you manage AI prompts in your Laravel application, making them as maintainable and organized as the rest of your codebase.

## Next Steps

- Explore [Conditional Logic](/guide/conditional-logic) to make your prompts dynamic
- Learn about [Message Types](/guide/messages) and when to use each
- See [Examples](/examples/basic-chat) of view-based prompts in action 