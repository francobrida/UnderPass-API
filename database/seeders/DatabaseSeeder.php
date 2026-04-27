<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Event;
use App\Models\Genre;
use App\Models\Vibecheck;
use App\Enums\UserRole;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
        ]);

        $organizer = User::factory()->create([
            'name' => 'Main Organizer',
            'email' => 'organizer@test.com',
            'role' => UserRole::ORGANIZER,
        ]);

        $clubber = User::factory()->create([
            'name' => 'Pepe Clubber',
            'email' => 'clubber@test.com',
            'role' => UserRole::CLUBBER,
        ]);

        $crowd = User::factory(10)->create();
        
        for ($i = 0; $i < 8; $i++) {
            $event = Event::factory()->create([
                'user_id' => $organizer->id,
                'is_verified' => true,
            ]);

            $randomKeys = array_rand($allGenreIds, rand(1, 2));
        
            $selectedIds = [];
            if (is_array($randomKeys)) {
                foreach ($randomKeys as $key) {
                    $selectedIds[] = $allGenreIds[$key];
                }
            } else {
                $selectedIds[] = $allGenreIds[$randomKeys];
            }

            $event->genres()->attach($selectedIds);

            Vibecheck::create([
                'event_id' => $event->id,
                'user_id' => $crowd->random()->id,
                'sound_score' => rand(3, 5),
                'safe_space_score' => rand(4, 5),
                'comment' => 'Nice mood, bartender is an asshole though.'
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            $event = Event::factory()->create([
                'user_id' => $crowd->random()->id,
                'is_verified' => false,
            ]);

            $randomGenreId = $allGenreIds[array_rand($allGenreIds)];
            
            $event->genres()->attach($randomGenreId);
        }

        $vouchEvent = Event::factory()->create([
            'title' => 'Underground Secret Session',
            'is_verified' => false,
            'user_id' => $crowd->random()->id
        ]);
        
        $usersForVouch = $crowd->take(2);
        foreach ($usersForVouch as $user) {
            $vouchEvent->vouches()->attach($user->id);
        }

        $pastEvents = Event::where('is_verified', true)->limit(2)->get();
        foreach ($pastEvents as $event) {
            $clubber->stampedEvents()->attach($event->id, ['scanned_at' => now()]);
        }
        
            try {
                $clientExists = \Illuminate\Support\Facades\DB::table('oauth_clients')
                    ->where('personal_access_client', 1)
                    ->exists();

                if (!$clientExists) {
                    $clientId = \Illuminate\Support\Facades\DB::table('oauth_clients')->insertGetId([
                        'user_id' => null,
                        'name' => 'UnderPass Personal Access Client',
                        'secret' => \Illuminate\Support\Str::random(40),
                        'provider' => 'users',
                        'redirect' => 'http://localhost',
                        'personal_access_client' => 1,
                        'password_client' => 0,
                        'revoked' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    \Illuminate\Support\Facades\DB::table('oauth_personal_access_clients')->insert([
                        'client_id' => $clientId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->command->info('Personal access client created successfully.');
                }
            } catch (\Exception $e) {
                $this->command->warn('Passport tables not ready yet, skipping client creation...');
            }
    }
}