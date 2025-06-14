---
layout: home

hero:
  name: "Converse"
  text: "AI Conversation Management for Laravel"
  tagline: Store and manage AI conversation history with any LLM provider. Built for real-world applications.
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/elliottlawson/converse

features:
  - icon: 🔌
    title: Provider Agnostic
    details: Works seamlessly with OpenAI, Anthropic, Google, and any other AI provider
    
  - icon: 💬
    title: Type-Safe Messages
    details: Dedicated methods for each message type with full TypeScript support
    
  - icon: 🌊
    title: Streaming Support
    details: Elegant handling of streaming responses with automatic chunk storage
    
  - icon: 📡
    title: Real-time Updates
    details: Built-in Laravel Broadcasting support for live conversation updates
    
  - icon: 🔐
    title: Soft Deletes
    details: Full soft delete support with automatic cascading deletes
    
  - icon: 🎯
    title: Developer Friendly
    details: Fluent API, conditional helpers, and Blade view integration

---

## Quick Start

Install the package via Composer:

```bash
composer require elliottlawson/converse
```

Add the trait to your User model:

```php
use ElliottLawson\Converse\Traits\HasAIConversations;

class User extends Model
{
    use HasAIConversations;
}
```

Start a conversation:

```php
$conversation = $user->startConversation(['title' => 'My Chat']);

$conversation
    ->addSystemMessage('You are a helpful assistant')
    ->addUserMessage('Hello!')
    ->addAssistantMessage('Hi! How can I help you today?');
```

[Learn more in the documentation →](/guide/getting-started) 