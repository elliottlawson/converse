# Getting Started

This guide will help you get up and running with Converse quickly. For detailed installation instructions, see the [Installation Guide](/guide/installation).

## Quick Start

### 1. Install the Package

```bash
composer require elliottlawson/converse
```

### 2. Run Migrations

```bash
php artisan migrate
```

That's it! You're ready to start using Converse.

## Basic Setup

### 1. Add the Trait to Your Model

Add the `HasAIConversations` trait to any model that should have conversations:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ElliottLawson\Converse\Traits\HasAIConversations;

class User extends Model
{
    use HasAIConversations;
}
```

### 2. Create Your First Conversation

```php
$user = User::find(1);

// Start a new conversation
$conversation = $user->startConversation([
    'title' => 'My First AI Chat'
]);
```

### 3. Add Messages

```php
// Add a system message to set the context
$conversation->addSystemMessage('You are a helpful assistant.');

// Add a user message
$conversation->addUserMessage('What is Laravel?');

// Add an assistant response
$conversation->addAssistantMessage(
    'Laravel is a PHP web application framework with expressive, elegant syntax...'
);
```

## Fluent API

Converse provides a fluent API for building conversations:

```php
$conversation = $user->startConversation(['title' => 'Laravel Help'])
    ->addSystemMessage('You are a Laravel expert.')
    ->addUserMessage('How do I create a middleware?')
    ->addAssistantMessage('To create a middleware in Laravel, you can use the artisan command...')
    ->addUserMessage('Can you show me an example?')
    ->addAssistantMessage("Here's a simple example of a Laravel middleware...");
```

## Next Steps

- Learn about [different message types](/guide/messages)
- Explore [streaming responses](/guide/streaming)
- Set up [real-time updates](/guide/events) with Laravel Broadcasting
- Discover [advanced features](/guide/conditional-logic) like conditional logic 