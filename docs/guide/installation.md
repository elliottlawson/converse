# Installation

This guide covers the installation and configuration of Laravel Converse in detail.

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or 12.0
- A database supported by Laravel (MySQL, PostgreSQL, SQLite, SQL Server)

## Installation via Composer

Install the package using Composer:

```bash
composer require elliottlawson/converse
```

## Service Provider

Laravel will automatically discover the `ConverseServiceProvider`. This provider:
- Registers the configuration
- Loads migrations
- Sets up event listeners

## Basic Setup (Zero Configuration)

For most applications, you can start using Laravel Converse immediately after installation:

```bash
# Run migrations
php artisan migrate
```

That's it! The package will use sensible defaults and you can start creating conversations.

## Advanced Configuration

### Publishing the Config File

If you need to customize settings, publish the configuration file:

```bash
php artisan vendor:publish --tag=converse-config
```

This creates `config/converse.php` with the following options:

```php
return [
    // Customize table names
    'tables' => [
        'conversations' => 'ai_conversations',
        'messages' => 'ai_messages', 
        'message_chunks' => 'ai_message_chunks',
        'message_attachments' => 'ai_message_attachments',
    ],

    // Override model classes
    'models' => [
        'conversation' => \ElliottLawson\Converse\Models\Conversation::class,
        'message' => \ElliottLawson\Converse\Models\Message::class,
        // ... other models
    ],

    // Attachment storage
    'attachments' => [
        'disk' => env('CONVERSE_DISK', 'local'),
        'path' => env('CONVERSE_PATH', 'conversations'),
    ],

    // Auto-cleanup settings
    'prune_after_days' => env('CONVERSE_PRUNE_DAYS', null),

    // Broadcasting settings
    'broadcasting' => [
        'enabled' => env('CONVERSE_BROADCASTING_ENABLED', true),
        'queue' => env('CONVERSE_BROADCAST_QUEUE', null),
    ],
];
```

### Customizing Table Names

To use custom table names, update the config **before** running migrations:

```php
// config/converse.php
'tables' => [
    'conversations' => 'chat_conversations',
    'messages' => 'chat_messages',
    'message_chunks' => 'chat_message_chunks', 
    'message_attachments' => 'chat_attachments',
],
```

### Publishing Migrations

If you need to modify the database schema, publish the migrations:

```bash
php artisan vendor:publish --tag=converse-migrations
```

This allows you to:
- Add custom columns
- Modify indexes
- Change foreign key constraints
- Add database-specific optimizations

Example of adding a custom column:

```php
// In the published migration file
Schema::create('ai_conversations', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->nullableMorphs('conversationable');
    $table->string('title')->nullable();
    $table->json('metadata')->nullable();
    
    // Add your custom columns
    $table->string('department')->nullable();
    $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['conversationable_type', 'conversationable_id']);
    $table->index('created_at');
});
```

## Environment Variables

Configure the package using environment variables:

```env
# Attachment storage
CONVERSE_DISK=s3
CONVERSE_PATH=ai-conversations

# Auto-cleanup (days)
CONVERSE_PRUNE_DAYS=90

# Broadcasting
CONVERSE_BROADCASTING_ENABLED=true
CONVERSE_BROADCAST_QUEUE=broadcasting
```

## Verifying Installation

After installation, verify everything is working:

```php
use ElliottLawson\Converse\Models\Conversation;

// Create a test conversation
$conversation = Conversation::create([
    'title' => 'Test Conversation'
]);

$conversation->addUserMessage('Hello!')
    ->addAssistantMessage('Hi there!');

// Check if messages were created
dd($conversation->messages->toArray());
```

## Troubleshooting

### Migration Errors

If you encounter migration errors:

1. Check for table name conflicts
2. Ensure your database supports JSON columns
3. Verify foreign key constraints

### Configuration Not Loading

1. Clear config cache: `php artisan config:clear`
2. Check if service provider is registered
3. Verify config file syntax

### Class Not Found Errors

1. Run `composer dump-autoload`
2. Clear Laravel caches: `php artisan cache:clear`
3. Check namespace imports

## Next Steps

- Continue to [Getting Started](/guide/getting-started) for basic usage
- Learn about [Conversations](/guide/conversations)
- Explore [Message Types](/guide/messages) 