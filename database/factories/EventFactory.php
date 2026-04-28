<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 
            'title' => $this->faker->sentence(3),
            'lineup' => $this->faker->name() . ', ' . $this->faker->name(),
            'description' => $this->faker->paragraph(),
            'date' => $this->faker->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'start_time' => '23:00',
            'end_time' => '06:00',
            'price' => $this->faker->randomElement([0, 10, 15, 20]),
            'price_info' => $this->faker->optional()->randomElement(['Incluye consumición', 'Early Bird', 'Taquilla únicamente']),
            'location_name' => $this->faker->company(),
            'neighborhood' => $this->faker->randomElement(['Poblenou', 'Eixample', 'Gràcia', 'Raval']),
            'is_verified' => true,
            'is_18_plus' => true,
            'stamp_token' => Str::random(32),
            'flyer' => 'images/flyers/party' . rand(1, 3) . '.jpg',
        ];
    }
}