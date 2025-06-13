<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Configure the table names used by the package. You can change these
    | if they conflict with existing tables in your application.
    |
    */
    'tables' => [
        'conversations' => 'conversations',
        'messages' => 'messages',
        'message_chunks' => 'message_chunks',
        'message_attachments' => 'message_attachments',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Model Classes
    |--------------------------------------------------------------------------
    |
    | You can override the default model classes if you need to extend
    | them with your own custom functionality.
    |
    */
    'models' => [
        'conversation' => \ElliottLawson\Converse\Models\Conversation::class,
        'message' => \ElliottLawson\Converse\Models\Message::class,
        'message_chunk' => \ElliottLawson\Converse\Models\MessageChunk::class,
        'message_attachment' => \ElliottLawson\Converse\Models\MessageAttachment::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where message attachments should be stored.
    |
    */
    'attachments' => [
        'disk' => env('CONVERSE_DISK', 'local'),
        'path' => env('CONVERSE_PATH', 'conversations'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | Set how long to keep conversations before they can be pruned.
    | Set to null to disable automatic pruning.
    |
    */
    'prune_after_days' => env('CONVERSE_PRUNE_DAYS', null),
    
    /*
    |--------------------------------------------------------------------------
    | Broadcasting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure broadcasting settings for real-time events.
    |
    */
    'broadcasting' => [
        'enabled' => env('CONVERSE_BROADCASTING_ENABLED', true),
        'queue' => env('CONVERSE_BROADCAST_QUEUE', null),
    ],
];