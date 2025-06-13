<?php

namespace ElliottLawson\Converse\Database\Factories;

use ElliottLawson\Converse\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'metadata' => [
                'provider' => $this->faker->randomElement(['openai', 'anthropic', 'google']),
                'model' => $this->faker->randomElement(['gpt-4', 'claude-3', 'gemini-pro']),
            ],
            'context' => [
                'system_prompt' => $this->faker->sentence(10),
            ],
        ];
    }

    public function withConversable($model): static
    {
        return $this->state(fn (array $attributes) => [
            'conversable_type' => get_class($model),
            'conversable_id' => $model->id,
        ]);
    }

    public function withTitle(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }

    public function withContext(array $context): static
    {
        return $this->state(fn (array $attributes) => [
            'context' => $context,
        ]);
    }
}
