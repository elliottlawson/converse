# What is Converse?

Converse is a comprehensive package for storing and managing AI conversation history in Laravel applications. It provides a robust, database-backed solution for maintaining conversation context across multiple AI interactions.

## The Problem It Solves

When building AI-powered applications, developers often face these challenges:

- **Conversation Persistence**: How to store conversation history between requests
- **Context Management**: How to maintain context across multiple interactions
- **Provider Flexibility**: How to work with different AI providers without vendor lock-in
- **Streaming Responses**: How to handle and store real-time streaming responses
- **Scalability**: How to efficiently manage thousands of conversations

Converse addresses all these challenges with an elegant, Laravel-native solution.

## Key Benefits

### 🏗️ Built for Laravel

Designed from the ground up to feel like a natural part of your Laravel application:
- Uses Eloquent models and relationships
- Integrates with Laravel's event system
- Supports Laravel Broadcasting for real-time features
- Follows Laravel conventions and best practices

### 🔄 Provider Agnostic

Work with any AI provider without changing your code:
- OpenAI (GPT-3.5, GPT-4, etc.)
- Anthropic (Claude)
- Google (Gemini/Bard)
- Local models (Ollama, etc.)
- Custom providers

### 💾 Complete Conversation Management

Everything you need to manage AI conversations:
- Automatic conversation and message persistence
- Soft deletes with cascading
- Message chunking for streaming responses
- Metadata storage for provider-specific data
- File attachments support

### 🚀 Production Ready

Built with real-world applications in mind:
- Efficient database queries
- Support for high-volume applications
- Comprehensive event system for analytics
- Built-in support for conversation branching
- UUID support for public-facing URLs

## How It Works

Converse provides a simple, intuitive API for managing conversations:

```php
// Start a conversation
$conversation = $user->startConversation(['title' => 'Customer Support']);

// Add messages
$conversation
    ->addSystemMessage('You are a helpful customer support agent')
    ->addUserMessage('I need help with my order')
    ->addAssistantMessage('I\'d be happy to help you with your order...');

// Messages are automatically persisted to the database
```

## Use Cases

Converse is perfect for:

- **Customer Support Chatbots**: Maintain conversation history across sessions
- **AI Writing Assistants**: Store document revision history with AI feedback
- **Code Review Tools**: Track AI-powered code review conversations
- **Educational Platforms**: Manage student-AI tutor interactions
- **Healthcare Applications**: Maintain HIPAA-compliant conversation logs
- **Any AI-Powered Application**: Where conversation history matters

## Architecture Overview

Converse uses a simple but powerful architecture:

- **Conversations**: Top-level container for a chat session
- **Messages**: Individual messages within a conversation
- **Message Chunks**: For streaming responses
- **Attachments**: File attachments for messages

All models support:
- Soft deletes with cascading
- JSON metadata fields
- Polymorphic relationships
- Event dispatching

## Why Choose Converse?

### Compared to Building Your Own

- **Save Development Time**: Months of development work already done
- **Battle-Tested**: Used in production applications
- **Maintained**: Regular updates and bug fixes
- **Community**: Growing community of users

### Compared to Other Solutions

- **Laravel Native**: Not a generic solution adapted for Laravel
- **No Vendor Lock-in**: Your data stays in your database
- **Flexible**: Adapt to your specific needs
- **Open Source**: MIT licensed, free to use and modify

## Next Steps

Ready to get started? Check out [Installation](/guide/installation) to install Converse, then continue to [Setup](/guide/getting-started) to create your first AI-powered conversation! 