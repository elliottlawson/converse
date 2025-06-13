<?php

namespace ElliottLawson\Converse\Database\Factories;

use ElliottLawson\Converse\Models\Message;
use ElliottLawson\Converse\Models\Conversation;
use ElliottLawson\Converse\Enums\MessageRole;
use ElliottLawson\Converse\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $isComplete = $this->faker->boolean(80);
        
        return [
            'conversation_id' => Conversation::factory(),
            'role' => $this->faker->randomElement(MessageRole::cases()),
            'content' => $isComplete ? $this->faker->paragraph() : '',
            'metadata' => [
                'model' => $this->faker->randomElement(['gpt-4', 'claude-3', 'gemini-pro']),
                'tokens' => $this->faker->numberBetween(10, 1000),
            ],
            'status' => $isComplete ? MessageStatus::Success : MessageStatus::Pending,
            'is_complete' => $isComplete,
            'completed_at' => $isComplete ? now() : null,
        ];
    }

    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => MessageRole::User,
        ]);
    }

    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => MessageRole::Assistant,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => MessageRole::System,
        ]);
    }

    public function streaming(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => '',
            'status' => MessageStatus::Pending,
            'is_complete' => false,
            'completed_at' => null,
            'metadata' => array_merge($attributes['metadata'] ?? [], ['streamed' => true]),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageStatus::Success,
            'is_complete' => true,
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageStatus::Error,
            'is_complete' => true,
            'completed_at' => now(),
            'metadata' => array_merge($attributes['metadata'] ?? [], ['error' => 'An error occurred']),
        ]);
    }
}