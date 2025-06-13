<?php

namespace ElliottLawson\Converse;

use Illuminate\Support\ServiceProvider;

class ConverseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/converse.php',
            'converse'
        );
    }
    
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/converse.php' => config_path('converse.php'),
            ], 'converse-config');
            
            $this->publishMigrations();
        }
        
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
    
    protected function publishMigrations(): void
    {
        if (! class_exists('CreateConversationsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/2025_12_06_000001_create_conversations_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_conversations_table.php'),
            ], 'converse-migrations');
        }
        
        if (! class_exists('CreateMessagesTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/2025_12_06_000002_create_messages_table.php' => database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_create_messages_table.php'),
            ], 'converse-migrations');
        }
        
        if (! class_exists('CreateMessageChunksTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/2025_12_06_000003_create_message_chunks_table.php' => database_path('migrations/' . date('Y_m_d_His', time() + 2) . '_create_message_chunks_table.php'),
            ], 'converse-migrations');
        }
        
        if (! class_exists('CreateMessageAttachmentsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/2025_12_06_000004_create_message_attachments_table.php' => database_path('migrations/' . date('Y_m_d_His', time() + 3) . '_create_message_attachments_table.php'),
            ], 'converse-migrations');
        }
    }
}