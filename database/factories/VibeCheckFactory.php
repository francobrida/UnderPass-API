<?php

namespace Database\Factories;

use App\Models\VibeCheck;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VibeCheckFactory extends Factory
{
    protected $model = VibeCheck::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(), 
            'user_id' => User::factory(),
            'sound_score' => $this->faker->numberBetween(1, 5),
            'safe_space_score' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence(), 
        ];
    }
}