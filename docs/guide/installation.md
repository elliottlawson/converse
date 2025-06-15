# Installation

This guide covers installing and configuring Converse in your Laravel application.

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or higher
- A database supported by Laravel (MySQL, PostgreSQL, SQLite, SQL Server)

## Installation

Install the package via Composer:

```bash
composer require elliottlawson/converse
```

Run the migrations:

```bash
php artisan migrate
```

That's it! Converse is now installed with sensible defaults.

### Upgrading from Earlier Versions

If you installed Converse before version 0.1.5, run:

```bash
php artisan converse:upgrade-uuid
```

This command:
- Adds the UUID column to your existing messages table
- Automatically generates UUIDs for all existing messages

## Configuration (Optional)

### Publishing the Config File

If you need to customize settings, you can publish the config:

```bash
php artisan vendor:publish --tag=converse-config
```

This creates `config/converse.php` where you can customize:

### Table Names

If you need custom table names, update the config **before** running migrations:

```php
// config/converse.php
'tables' => [
    'conversations' => 'chat_conversations',
    'messages' => 'chat_messages',
    'message_chunks' => 'chat_message_chunks', 
    'message_attachments' => 'chat_attachments',
],
```

### Model Classes

Override the default models if needed:

```php
'models' => [
    'conversation' => \App\Models\CustomConversation::class,
    'message' => \App\Models\CustomMessage::class,
],
```

### Storage Settings

Configure where attachments are stored:

```php
'attachments' => [
    'disk' => env('CONVERSE_DISK', 'local'),
    'path' => env('CONVERSE_PATH', 'conversations'),
],
```

### Broadcasting

Configure real-time broadcasting:

```php
'broadcasting' => [
    'enabled' => env('CONVERSE_BROADCASTING_ENABLED', true),
    'queue' => env('CONVERSE_BROADCAST_QUEUE', null),
],
```

## Environment Variables

Available environment variables:

```bash
# Attachment storage
CONVERSE_DISK=s3
CONVERSE_PATH=ai-conversations

# Auto-cleanup (days)
CONVERSE_PRUNE_DAYS=90

# Broadcasting
CONVERSE_BROADCASTING_ENABLED=true
CONVERSE_BROADCAST_QUEUE=broadcasting
```

## Publishing Migrations (Advanced)

If you need to modify the database schema:

```bash
php artisan vendor:publish --tag=converse-migrations
```

Then edit the migrations in `database/migrations/` before running them.

## Service Provider

Laravel automatically discovers the `ConverseServiceProvider`. It handles:
- Configuration registration
- Migration loading
- Event listener setup

No manual registration is needed.

## Troubleshooting

### Migration Errors

- **Table already exists**: You may have conflicting table names. Use custom table names in the config.
- **JSON column errors**: Ensure your database version supports JSON columns.
- **Foreign key errors**: Check that referenced tables exist.

### Configuration Issues

```bash
# Clear config cache
php artisan config:clear

# Clear all caches
php artisan optimize:clear
```

### Class Not Found

```bash
# Regenerate autoload files
composer dump-autoload

# Clear compiled classes
php artisan clear-compiled
```

## Next Steps

- Continue to [Setup](/guide/getting-started) to create your first conversation
- Learn about the [Conversation Model](/guide/conversations)
- Explore [Message Types](/guide/messages) 