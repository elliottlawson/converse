<?php

namespace ElliottLawson\Converse\Database\Factories;

use ElliottLawson\Converse\Models\MessageChunk;
use ElliottLawson\Converse\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageChunkFactory extends Factory
{
    protected $model = MessageChunk::class;

    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'content' => $this->faker->words(3, true),
            'sequence' => 0,
            'metadata' => [],
        ];
    }

    public function forMessage(Message $message): static
    {
        return $this->state(fn (array $attributes) => [
            'message_id' => $message->id,
        ]);
    }

    public function withSequence(int $sequence): static
    {
        return $this->state(fn (array $attributes) => [
            'sequence' => $sequence,
        ]);
    }
}