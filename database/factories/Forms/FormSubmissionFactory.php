<?php

namespace Database\Factories\Forms;

use App\Forms\Models\FormSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FormSubmission> */
class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    public function definition(): array
    {
        return [
            'type' => 'contact',
            'status' => FormSubmission::STATUS_NEW,
            'data' => [
                'name' => $this->faker->name(),
                'email' => $this->faker->safeEmail(),
                'message' => $this->faker->paragraph(),
            ],
            'subject_type' => null,
            'subject_id' => null,
            'meta' => [
                'url' => $this->faker->url(),
                'ip' => $this->faker->ipv4(),
                'user_agent' => 'PestTest/1.0',
                'referer' => null,
            ],
            'locale' => 'uk',
            'handled_at' => null,
        ];
    }

    public function order(): static
    {
        return $this->state(fn () => [
            'type' => 'order',
            'data' => [
                'name' => $this->faker->name(),
                'phone' => $this->faker->phoneNumber(),
                'email' => $this->faker->safeEmail(),
                'qty' => $this->faker->numberBetween(1, 5),
                'note' => $this->faker->optional()->sentence(),
            ],
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'handled_at' => $status === FormSubmission::STATUS_PROCESSED ? now() : null,
        ]);
    }
}
