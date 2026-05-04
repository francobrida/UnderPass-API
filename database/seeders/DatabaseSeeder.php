<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Event;
use App\Models\Genre;
use App\Models\VibeCheck;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $genreNames = ['Techno', 'Industrial', 'House', 'Drum n Bass', 'Electro Pop', 'Acid'];
        $genres = [];

        foreach ($genreNames as $name) {
            $genre = Genre::create([
                'name' => $name,
                'slug' => Str::slug($name)
            ]);
            
            $genres[$name] = $genre->id; 
        }
        $allGenreIds = array_values($genres);

        $admin = User::factory()->create([
            'name' => 'Admin Underpass',
            'email' => 'admin@underpass.com',
            'role' => UserRole::ADMIN,
            'points' => 1200,
        ]);

        $organizer = User::factory()->create([
            'name' => 'Main Organizer',
            'email' => 'organizer@test.com',
            'role' => UserRole::ORGANIZER,
            'points' => 500,
        ]);

        $clubber = User::factory()->create([
            'name' => 'Pepe Clubber',
            'email' => 'clubber@test.com',
            'role' => UserRole::CLUBBER,
            'points' => 1500, 
        ]);

        $crowd = User::factory(10)->create();

        for ($i = 1; $i <= 5; $i++) {
            $event = Event::factory()->create([
                'title' => "Underground Rave Session #{$i}",
                'user_id' => $organizer->id,
                'is_verified' => true,
                'date' => Carbon::now()->addDays($i)->format('Y-m-d'),
                'start_time' => '23:00',
                'end_time' => '06:00',
            ]);

            $event->genres()->attach($allGenreIds[array_rand($allGenreIds)]);
        }

        $todayEvent = Event::factory()->create([
            'title' => 'The Live Experience [TODAY]',
            'user_id' => $organizer->id,
            'is_verified' => true,
            'date' => Carbon::today()->format('Y-m-d'),
            'start_time' => '00:00',
            'end_time' => '23:59',
            'stamp_token' => 'SCAN-THIS-QR-123',
        ]);
        $todayEvent->genres()->attach($allGenreIds[array_rand($allGenreIds)]);

        $pastVibeCheckReady = Event::factory()->create([
            'title' => 'Acid Techno Rave Past',
            'user_id' => $organizer->id,
            'is_verified' => true,
            'date' => Carbon::yesterday()->format('Y-m-d'),
            'start_time' => '18:00',
            'end_time' => '23:00',
        ]);
        $pastVibeCheckReady->genres()->attach($genres['Acid'] ?? $allGenreIds[0]);

        $clubber->stampedEvents()->attach($pastVibeCheckReady->id, ['scanned_at' => now()->subDay()]);

        $pastWithVibechecks = Event::factory()->create([
            'title' => 'Industrial Hardcore Memories',
            'user_id' => $organizer->id,
            'is_verified' => true,
            'date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'start_time' => '22:00',
            'end_time' => '05:00',
        ]);
        $pastWithVibechecks->genres()->attach($genres['Industrial'] ?? $allGenreIds[0]);

  
        foreach ($crowd->take(3) as $user) {
            $user->stampedEvents()->attach($pastWithVibechecks->id, ['scanned_at' => now()->subDays(5)]);
            
            VibeCheck::create([
                'event_id' => $pastWithVibechecks->id,
                'user_id' => $user->id,
                'sound_score' => rand(3, 5),
                'safe_space_score' => rand(4, 5),
                'comment' => 'Amazing industrial vibe. Visuals were top tier.'
            ]);
        }

        $waitingEvent1 = Event::factory()->create([
            'title' => 'House Secret Gathering [WAITING]',
            'user_id' => $crowd->random()->id,
            'is_verified' => false,
            'date' => Carbon::now()->addDays(15)->format('Y-m-d'),
        ]);
        $waitingEvent1->genres()->attach($genres['House'] ?? $allGenreIds[0]);

        foreach ($crowd->take(2) as $vUser) {
            $waitingEvent1->vouches()->attach($vUser->id);
        }

        $waitingEvent2 = Event::factory()->create([
            'title' => 'Acid Electro Lab [WAITING - VOTE ME]',
            'user_id' => $crowd->random()->id,
            'is_verified' => false,
            'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
        ]);
        $waitingEvent2->genres()->attach($genres['Acid'] ?? $allGenreIds[0]);

       
        $waitingEvent3 = Event::factory()->create([
            'title' => 'Industrial Base [VOTED BY PEPE]',
            'user_id' => $crowd->random()->id,
            'is_verified' => false,
            'date' => Carbon::now()->addDays(12)->format('Y-m-d'),
        ]);
        $waitingEvent3->genres()->attach($genres['Industrial'] ?? $allGenreIds[0]);
        $waitingEvent3->vouches()->attach($clubber->id);

        $this->command->info('Database fully seeded for Underpass web.');
    }
}