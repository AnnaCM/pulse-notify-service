<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'batch_id' => (string) \Illuminate\Support\Str::uuid(),
            'channel' => 'email',
            'recipient' => fake()->email(),
            'content' => fake()->sentence(),
            'status' => 'pending',
            'priority' => 'normal',
            'attempts' => 0,
            'scheduled_at' => null,
            'sent_at' => null,
            'external_id' => null,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
