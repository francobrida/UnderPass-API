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
        // Poblamos la base de datos con todos los géneros
        $this->call([
            AddMoreGenresSeeder::class,
        ]);

        $allGenreIds = \App\Models\Genre::pluck('id')->toArray();

        $admin = User::where('email', 'admin@underpass.com')->first();
        if (!$admin) {
            $admin = User::factory()->create([
                'name' => 'Admin Underpass',
                'email' => 'admin@underpass.com',
                'password' => 'password',
                'role' => UserRole::ADMIN,
                'points' => 1200,
            ]);
        }

        $organizer = User::where('email', 'organizer@test.com')->first();
        if (!$organizer) {
            $organizer = User::factory()->create([
                'name' => 'Main Organizer',
                'email' => 'organizer@test.com',
                'password' => 'password',
                'role' => UserRole::ORGANIZER,
                'points' => 500,
            ]);
        }

        $clubber = User::where('email', 'clubber@test.com')->first();
        if (!$clubber) {
            $clubber = User::factory()->create([
                'name' => 'Pepe Clubber',
                'email' => 'clubber@test.com',
                'password' => 'password',
                'role' => UserRole::CLUBBER,
                'points' => 1500,
            ]);
        }

        for ($i = 1; $i <= 3; $i++) {
            $event = Event::factory()->create([
                'title' => "TEST EVENT {$i}",
                'user_id' => $organizer->id,
                'is_verified' => true,
                'date' => Carbon::now()->addDays($i * 30)->format('Y-m-d'),
                'start_time' => '23:00',
                'end_time' => '06:00',
            ]);

            if (!empty($allGenreIds)) {
                $event->genres()->attach($allGenreIds[array_rand($allGenreIds)]);
            }
        }

        $this->command->info('Database fully seeded with exactly 3 test events for Underpass API.');

        
        $crowd = User::factory(10)->create();

        for ($i = 1; $i <= 5; $i++) {
            $event = Event::factory()->create([
                'title' => "Underground Rave Session #{$i}",
                'user_id' => $organizer->id,
                'is_verified' => true,
                'date' => Carbon::now()->addDays($i * 25)->format('Y-m-d'),
                'start_time' => '23:00',
                'end_time' => '06:00',
            ]);

            $event->genres()->attach($allGenreIds[array_rand($allGenreIds)]);
        }

        $todayEvent = Event::where('stamp_token', 'SCAN-THIS-QR-123')->first();
        if (!$todayEvent) {
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
        }

        $pastVibeCheckReady = Event::where('title', 'Acid Techno Rave Past')->first();
        if (!$pastVibeCheckReady) {
            $pastVibeCheckReady = Event::factory()->create([
                'title' => 'Acid Techno Rave Past',
                'user_id' => $organizer->id,
                'is_verified' => true,
                'date' => Carbon::yesterday()->format('Y-m-d'),
                'start_time' => '18:00',
                'end_time' => '23:00',
            ]);
            $pastVibeCheckReady->genres()->attach($allGenreIds[array_rand($allGenreIds)]);
        }

        $hasStamp = \DB::table('stamps')
            ->where('user_id', $clubber->id)
            ->where('event_id', $pastVibeCheckReady->id)
            ->exists();

        if (!$hasStamp) {
            $clubber->stampedEvents()->attach($pastVibeCheckReady->id, ['scanned_at' => now()->subDay()]);
        }

        $pastWithVibechecks = Event::where('title', 'Industrial Hardcore Memories')->first();
        if (!$pastWithVibechecks) {
            $pastWithVibechecks = Event::factory()->create([
                'title' => 'Industrial Hardcore Memories',
                'user_id' => $organizer->id,
                'is_verified' => true,
                'date' => Carbon::now()->subDays(5)->format('Y-m-d'),
                'start_time' => '22:00',
                'end_time' => '05:00',
            ]);
            $pastWithVibechecks->genres()->attach($allGenreIds[array_rand($allGenreIds)]);

            foreach ($crowd->take(3) as $user) {
                $user->stampedEvents()->attach($pastWithVibechecks->id, ['scanned_at' => now()->subDays(5)]);
                
                \App\Models\VibeCheck::create([
                    'event_id' => $pastWithVibechecks->id,
                    'user_id' => $user->id,
                    'sound_score' => rand(3, 5),
                    'safe_space_score' => rand(4, 5),
                    'comment' => 'Amazing industrial vibe. Visuals were top tier.'
                ]);
            }
        }

        $waitingEvent1 = Event::factory()->create([
            'title' => 'House partyyy [WAITING]',
            'user_id' => $crowd->random()->id,
            'is_verified' => false,
            'date' => Carbon::now()->addDays(40)->format('Y-m-d'),
        ]);
        $waitingEvent1->genres()->attach($allGenreIds[array_rand($allGenreIds)]);

        foreach ($crowd->take(2) as $vUser) {
            $waitingEvent1->vouches()->attach($vUser->id);
        }

        $waitingEvent2 = Event::factory()->create([
            'title' => 'Acid Electro Lab [WAITING - VOTE ME]',
            'user_id' => $crowd->random()->id,
            'is_verified' => false,
            'date' => Carbon::now()->addDays(55)->format('Y-m-d'),
        ]);
        $waitingEvent2->genres()->attach($allGenreIds[array_rand($allGenreIds)]);

        $waitingEvent3 = Event::factory()->create([
            'title' => 'Industrial nave [VOTED BY PEPE]',
            'user_id' => $crowd->random()->id,
            'is_verified' => false,
            'date' => Carbon::now()->addDays(70)->format('Y-m-d'),
        ]);
        $waitingEvent3->genres()->attach($allGenreIds[array_rand($allGenreIds)]);
        $waitingEvent3->vouches()->attach($clubber->id);
    }
}