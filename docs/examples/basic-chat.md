# Basic Chat Example

This example demonstrates how to build a simple chat interface using Laravel Converse with OpenAI.

## Controller Setup

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $conversations = $user->conversations()->latest()->get();
        
        return view('chat.index', compact('conversations'));
    }

    public function show(Request $request, $conversationId)
    {
        $conversation = $request->user()
            ->conversations()
            ->with('messages')
            ->findOrFail($conversationId);
            
        return view('chat.show', compact('conversation'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $conversation = $request->user()->startConversation([
            'title' => $validated['title'],
            'metadata' => [
                'provider' => 'openai',
                'model' => 'gpt-4',
            ],
        ]);

        return redirect()->route('chat.show', $conversation);
    }

    public function sendMessage(Request $request, $conversationId)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $conversation = $request->user()
            ->conversations()
            ->findOrFail($conversationId);

        // Add user message
        $conversation->addUserMessage($validated['message']);

        // Prepare messages for OpenAI
        $messages = $this->prepareMessagesForOpenAI($conversation);

        // Get AI response
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => $messages,
        ]);

        // Add assistant response
        $assistantMessage = $conversation->addAssistantMessage(
            $response->choices[0]->message->content,
            ['usage' => $response->usage->toArray()]
        );

        return response()->json([
            'message' => $assistantMessage,
        ]);
    }

    private function prepareMessagesForOpenAI($conversation)
    {
        // Add system message if not exists
        if (!$conversation->messages()->where('role', 'system')->exists()) {
            $conversation->addSystemMessage('You are a helpful assistant.');
        }

        // Format messages for OpenAI
        return $conversation->messages->map(function ($message) {
            return [
                'role' => $message->role,
                'content' => $message->content,
            ];
        })->toArray();
    }
}
```

## Blade View

```blade
{{-- resources/views/chat/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="chat-container">
    <div class="chat-header">
        <h2>{{ $conversation->title }}</h2>
        <span class="text-muted">Started {{ $conversation->created_at->diffForHumans() }}</span>
    </div>

    <div class="messages-container" id="messages">
        @foreach($conversation->messages as $message)
            <div class="message message-{{ $message->role }}">
                <div class="message-role">{{ ucfirst($message->role) }}</div>
                <div class="message-content">{{ $message->content }}</div>
                <div class="message-time">{{ $message->created_at->format('h:i A') }}</div>
            </div>
        @endforeach
    </div>

    <form id="chat-form" class="chat-input-form">
        @csrf
        <div class="input-group">
            <input 
                type="text" 
                id="message-input" 
                class="form-control" 
                placeholder="Type your message..."
                autocomplete="off"
            >
            <button type="submit" class="btn btn-primary">Send</button>
        </div>
    </form>
</div>

<script>
document.getElementById('chat-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Clear input
    input.value = '';
    input.disabled = true;
    
    // Add user message to UI
    addMessageToUI('user', message);
    
    try {
        // Send message to server
        const response = await fetch(`/chat/{{ $conversation->id }}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
            },
            body: JSON.stringify({ message }),
        });
        
        const data = await response.json();
        
        // Add assistant message to UI
        addMessageToUI('assistant', data.message.content);
        
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to send message. Please try again.');
    } finally {
        input.disabled = false;
        input.focus();
    }
});

function addMessageToUI(role, content) {
    const messagesContainer = document.getElementById('messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${role}`;
    
    const time = new Date().toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit' 
    });
    
    messageDiv.innerHTML = `
        <div class="message-role">${role.charAt(0).toUpperCase() + role.slice(1)}</div>
        <div class="message-content">${content}</div>
        <div class="message-time">${time}</div>
    `;
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}
</script>
@endsection
```

## Routes

```php
// routes/web.php
use App\Http\Controllers\ChatController;

Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('chat.messages.store');
});
```

## Styling

```css
/* Basic chat styling */
.chat-container {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    height: 80vh;
}

.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f5f5f5;
}

.message {
    margin-bottom: 20px;
    padding: 15px;
    border-radius: 8px;
    background: white;
}

.message-user {
    background: #007bff;
    color: white;
    margin-left: 20%;
}

.message-assistant {
    background: white;
    margin-right: 20%;
}

.message-role {
    font-size: 0.85em;
    font-weight: bold;
    margin-bottom: 5px;
}

.message-time {
    font-size: 0.75em;
    opacity: 0.7;
    margin-top: 5px;
}

.chat-input-form {
    padding: 20px;
    background: white;
    border-top: 1px solid #ddd;
}
```

## Usage

1. Users can create new conversations with a title
2. Messages are automatically stored in the database
3. Conversation history persists between sessions
4. Each user has their own private conversations
5. Metadata tracks token usage for billing

This example provides a foundation that you can extend with features like:
- Streaming responses
- Message editing/deletion  
- Conversation search
- Export functionality
- Multi-provider support 