# Getting Started

Laravel Converse is a powerful package for managing AI conversation history in your Laravel applications. This guide will help you get up and running quickly.

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or 12.0
- A database supported by Laravel

## Installation

Install the package via Composer:

```bash
composer require elliottlawson/converse
```

## Configuration

Publish the configuration file and migrations:

```bash
php artisan vendor:publish --provider="ElliottLawson\Converse\ConverseServiceProvider"
```

This will create:
- `config/converse.php` - Configuration file
- Database migration files

Run the migrations:

```bash
php artisan migrate
```

## Basic Setup

### 1. Add the Trait to Your Model

Add the `HasAIConversations` trait to any model that should have conversations (typically User):

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
    'title' => 'My First AI Chat',
    'metadata' => [
        'provider' => 'openai',
        'model' => 'gpt-4',
    ],
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

Laravel Converse provides a fluent API for building conversations:

```php
$conversation = $user->startConversation(['title' => 'Laravel Help'])
    ->addSystemMessage('You are a Laravel expert.')
    ->addUserMessage('How do I create a middleware?')
    ->addAssistantMessage('To create a middleware in Laravel, you can use the artisan command...')
    ->addUserMessage('Can you show me an example?')
    ->addAssistantMessage('Here\'s a simple example of a Laravel middleware...');
```

## Next Steps

- Learn about [different message types](/guide/messages)
- Explore [streaming responses](/guide/streaming)
- Set up [real-time updates](/guide/events) with Laravel Broadcasting
- Discover [advanced features](/guide/conditional-logic) like conditional logic 