# Streaming Responses

Most AI providers stream responses token by token. Converse makes it easy to store these streams while maintaining a great user experience.

## Starting a Streaming Response

To begin streaming an assistant response:

```php
use ElliottLawson\Converse\Models\Message;

// Start streaming an assistant response
$message = $conversation->startStreamingAssistant([
    'model' => 'claude-3-5-sonnet',
    'request_id' => Str::uuid(),
]);
```

## Handling Stream Chunks

As chunks arrive from your AI provider, append them to the message:

```php
// In your streaming handler, append chunks as they arrive
foreach ($aiProvider->stream($prompt) as $chunk) {
    $message->appendChunk($chunk);
    
    // Broadcast to your frontend
    broadcast(new StreamUpdate($message, $chunk));
}
```

## Completing the Stream

When streaming completes successfully:

```php
$message->completeStreaming([
    'prompt_tokens' => 150,
    'completion_tokens' => 423,
    'total_tokens' => 573,
    'finish_reason' => 'stop',
    'duration_ms' => 2341,
]);
```

## Handling Failures

Handle streaming failures gracefully:

```php
if ($error) {
    $message->failStreaming('Connection lost', [
        'error_code' => 'NETWORK_ERROR',
        'attempted_retry' => true,
        'partial_response' => true,
    ]);
}
```

## Streaming User Input

You can also stream user input for voice transcription or real-time collaboration:

```php
$message = $conversation->startStreamingUser([
    'input_method' => 'voice',
    'language' => 'en-US',
]);

// As voice chunks are transcribed
foreach ($voiceTranscriber->stream($audioStream) as $text) {
    $message->appendChunk($text);
}

// When transcription is complete
$message->completeStreaming([
    'confidence' => 0.95,
    'duration_ms' => 5234,
]);
```

## Complete Example with OpenAI

Here's a complete example using OpenAI's streaming API:

```php
use OpenAI\Laravel\Facades\OpenAI;
use App\Events\StreamUpdate;

class StreamingChatController extends Controller
{
    public function stream(Request $request, Conversation $conversation)
    {
        // Start the streaming message
        $message = $conversation->startStreamingAssistant([
            'model' => 'gpt-4',
            'temperature' => 0.7,
        ]);

        // Prepare messages for OpenAI
        $messages = $conversation->messages->map(fn($m) => [
            'role' => $m->role,
            'content' => $m->content,
        ])->toArray();

        try {
            $stream = OpenAI::chat()->createStreamed([
                'model' => 'gpt-4',
                'messages' => $messages,
                'temperature' => 0.7,
                'stream' => true,
            ]);

            $fullContent = '';
            $chunkIndex = 0;

            foreach ($stream as $response) {
                if (isset($response->choices[0]->delta->content)) {
                    $chunk = $response->choices[0]->delta->content;
                    $fullContent .= $chunk;
                    
                    // Store chunk
                    $message->appendChunk($chunk);
                    
                    // Broadcast to frontend
                    broadcast(new StreamUpdate(
                        $conversation->id,
                        $message->id,
                        $chunk,
                        $chunkIndex++
                    ))->toOthers();
                }
            }

            // Complete the message
            $message->completeStreaming([
                'model' => 'gpt-4',
                'finish_reason' => 'stop',
            ]);

        } catch (\Exception $e) {
            $message->failStreaming($e->getMessage(), [
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }

        return response()->json(['message' => $message->fresh()]);
    }
}
```

## Frontend Integration

### Using React Hooks

Handle streaming updates with the Laravel Echo React hook:

```jsx
import { useEcho } from '@laravel/echo-react';
import { useState } from 'react';

function StreamingMessage({ conversationId, messageId }) {
    const [content, setContent] = useState('');
    
    useEcho(
        `private-conversation.${conversationId}`,
        'StreamUpdate',
        (e) => {
            if (e.messageId === messageId) {
                setContent(prev => prev + e.chunk);
            }
        }
    );
    
    return (
        <div className="message">
            {content}
            <span className="typing-indicator">●●●</span>
        </div>
    );
}
```

### Classic JavaScript Approach

```javascript
// Listen for streaming updates
Echo.private(`conversation.${conversationId}`)
    .listen('StreamUpdate', (e) => {
        // Append chunk to the message being displayed
        const messageElement = document.getElementById(`message-${e.messageId}`);
        if (messageElement) {
            messageElement.textContent += e.chunk;
        }
    });
```

## Message Chunks

Access chunks for a streamed message:

```php
// Get all chunks for a message
$chunks = $message->chunks;

foreach ($chunks as $chunk) {
    echo $chunk->content;
    echo "Received at: " . $chunk->created_at;
}

// Get chunks in order
$orderedChunks = $message->chunks()->orderBy('sequence')->get();
```

## Streaming States

Messages have different states during streaming:

```php
// Check if a message is currently streaming
if ($message->isStreaming()) {
    // Show loading indicator
}

// Check if streaming completed
if ($message->isCompleted()) {
    // Show full message
}

// Check if streaming failed
if ($message->isFailed()) {
    // Show error state
}
```

## Best Practices

1. **Always handle failures**: Network issues are common with streaming
2. **Store metadata**: Track token counts, duration, and model details
3. **Use broadcasting**: Keep your UI in sync with real-time updates
4. **Implement retry logic**: Allow users to retry failed streams
5. **Clean up partial responses**: Handle incomplete streams gracefully

## Performance Tips

### Chunk Batching

For very fast streams, consider batching chunks:

```php
$buffer = '';
$lastBroadcast = now();

foreach ($aiProvider->stream($prompt) as $chunk) {
    $buffer .= $chunk;
    
    // Broadcast every 100ms to avoid overwhelming the frontend
    if (now()->diffInMilliseconds($lastBroadcast) >= 100) {
        $message->appendChunk($buffer);
        broadcast(new StreamUpdate($message, $buffer));
        $buffer = '';
        $lastBroadcast = now();
    }
}

// Don't forget the last chunk
if ($buffer) {
    $message->appendChunk($buffer);
    broadcast(new StreamUpdate($message, $buffer));
}
```

### Database Optimization

Consider using database transactions for chunk storage:

```php
DB::transaction(function () use ($message, $chunks) {
    foreach ($chunks as $index => $chunk) {
        $message->chunks()->create([
            'content' => $chunk,
            'sequence' => $index,
        ]);
    }
});
```

## Next Steps

- Learn about [Events](/guide/events) fired during streaming
- See [Real-time Example](/examples/real-time) for complete implementation
- Explore [Message Chunks API](/api/message-chunks) reference 