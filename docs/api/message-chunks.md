# Message Chunks API Reference

The `MessageChunk` model is used to store incremental content during streaming responses from AI providers.

## Model Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Unique identifier |
| `message_id` | integer | Parent message ID |
| `content` | text | Chunk content |
| `sequence` | integer | Order index (0-based) |
| `metadata` | json | Additional chunk metadata |
| `created_at` | timestamp | Creation timestamp |

Note: MessageChunks don't have an `updated_at` timestamp as they are immutable once created.

## Relationships

```php
// Get the parent message
$message = $chunk->message;

// Get all chunks for a message (ordered)
$chunks = $message->chunks; // Automatically ordered by sequence
```

## Usage Examples

### Automatic Chunk Creation

Chunks are typically created automatically when using the `appendChunk` method on a message:

```php
// During streaming response handling
$message = $conversation->createAssistantMessage('', [
    'is_complete' => false,
    'status' => MessageStatus::Pending,
]);

// As chunks arrive from the AI provider
foreach ($stream as $chunk) {
    $message->appendChunk($chunk->content, [
        'delta' => $chunk->delta,
        'finish_reason' => $chunk->finish_reason,
    ]);
}

// Complete the message
$message->completeStreaming();
```

### Querying Chunks

```php
// Get all chunks for a message
$chunks = $message->chunks;

// Get chunks with specific metadata
$toolChunks = $message->chunks()
    ->whereJsonContains('metadata->type', 'tool_call')
    ->get();

// Count chunks
$chunkCount = $message->chunks()->count();

// Get chunk content in order
$fullContent = $message->chunks()
    ->orderBy('sequence')
    ->pluck('content')
    ->implode('');
```

### Rebuilding Content from Chunks

```php
// Verify message content matches chunks
$rebuiltContent = $message->chunks
    ->sortBy('sequence')
    ->pluck('content')
    ->implode('');

if ($rebuiltContent !== $message->content) {
    // Handle mismatch
    $message->update(['content' => $rebuiltContent]);
}
```

### Streaming with Progress Tracking

```php
class StreamHandler
{
    protected $message;
    protected $totalChunks = 0;
    
    public function handleStream($stream)
    {
        $this->message = $conversation->createAssistantMessage('', [
            'is_complete' => false,
        ]);
        
        foreach ($stream as $chunk) {
            $this->totalChunks++;
            
            $this->message->appendChunk($chunk->content, [
                'index' => $this->totalChunks,
                'timestamp' => now()->toIso8601String(),
            ]);
            
            // Broadcast progress
            broadcast(new StreamProgress(
                $this->message->conversation_id,
                $this->totalChunks
            ));
        }
        
        $this->message->completeStreaming([
            'total_chunks' => $this->totalChunks,
        ]);
    }
}
```

### Chunk Analysis

```php
// Analyze streaming performance
$conversation = Conversation::find($id);

$streamingStats = $conversation->messages()
    ->with('chunks')
    ->get()
    ->map(function ($message) {
        if ($message->chunks->isEmpty()) {
            return null;
        }
        
        $firstChunk = $message->chunks->first();
        $lastChunk = $message->chunks->last();
        
        return [
            'message_id' => $message->id,
            'chunk_count' => $message->chunks->count(),
            'total_size' => $message->chunks->sum(fn($c) => strlen($c->content)),
            'duration' => $lastChunk->created_at->diffInSeconds($firstChunk->created_at),
            'chunks_per_second' => $message->chunks->count() / max(1, 
                $lastChunk->created_at->diffInSeconds($firstChunk->created_at)
            ),
        ];
    })
    ->filter()
    ->values();
```

### Error Recovery

```php
// Handle incomplete streaming
$incompleteMessages = Message::streaming()
    ->where('created_at', '<', now()->subMinutes(5))
    ->with('chunks')
    ->get();

foreach ($incompleteMessages as $message) {
    // Mark as failed if no chunks received
    if ($message->chunks->isEmpty()) {
        $message->failStreaming('No response received', [
            'timeout' => true,
        ]);
        continue;
    }
    
    // Complete with partial content
    $message->completeStreaming([
        'partial' => true,
        'reason' => 'timeout',
    ]);
}
```

## Events

The MessageChunk creation triggers the `ChunkReceived` event, which includes both the message and the chunk:

```php
use ElliottLawson\Converse\Events\ChunkReceived;

class ChunkLogger
{
    public function handle(ChunkReceived $event)
    {
        Log::debug('Chunk received', [
            'conversation_id' => $event->message->conversation_id,
            'message_id' => $event->message->id,
            'chunk_sequence' => $event->chunk->sequence,
            'chunk_size' => strlen($event->chunk->content),
        ]);
    }
}
``` 