<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Event;
use App\Models\Genre;
use App\Models\VibeCheck;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        
        if (DB::table('oauth_clients')->where('personal_access_client', 1)->doesntExist()) {
            $clientId = DB::table('oauth_clients')->insertGetId([
                'name' => 'UnderPass Personal Access Client',
                'secret' => Str::random(40),
                'provider' => 'users',
                'redirect' => 'http://localhost',
                'personal_access_client' => 1,
                'password_client' => 0,
                'revoked' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('oauth_personal_access_clients')->insert([
                'client_id' => $clientId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $genreNames = ['Techno', 'Industrial', 'House', 'Drum n Bass', 'Electro Pop', 'Acid'];
        $allGenreIds = [];
        
        foreach ($genreNames as $name) {
            $genre = Genre::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $allGenreIds[] = $genre->id;
        }

        $admin = User::where('email', 'admin@underpass.com')->first();
        if (!$admin) {
            $admin = User::factory()->create([
                'name' => 'Admin Underpass',
                'email' => 'admin@underpass.com',
                'role' => UserRole::ADMIN,
                'password' => 'password',
            ]);
        }

        $organizer = User::where('email', 'organizer@test.com')->first();
        if (!$organizer) {
            $organizer = User::factory()->create([
                'name' => 'Main Organizer',
                'email' => 'organizer@test.com',
                'role' => UserRole::ORGANIZER,
                'password' => 'password',
            ]);
        }

        $clubber = User::where('email', 'clubber@test.com')->first();
        if (!$clubber) {
            $clubber = User::factory()->create([
                'name' => 'Pepe Clubber',
                'email' => 'clubber@test.com',
                'role' => UserRole::CLUBBER,
                'password' => 'password',
            ]);
        }

        if (Event::count() === 0) {
            $crowd = User::factory(10)->create();

            for ($i = 0; $i < 8; $i++) {
                $event = Event::factory()->create([
                    'user_id' => $organizer->id,
                    'is_verified' => true,
                ]);

                $randomKeys = array_rand($allGenreIds, rand(1, 2));
                $selectedIds = is_array($randomKeys) 
                    ? array_intersect_key($allGenreIds, array_flip($randomKeys)) 
                    : [$allGenreIds[$randomKeys]];

                $event->genres()->attach($selectedIds);

                VibeCheck::create([
                    'event_id' => $event->id,
                    'user_id' => $crowd->random()->id,
                    'sound_score' => rand(3, 5),
                    'safe_space_score' => rand(4, 5),
                    'comment' => 'Nice mood, bartender is an asshole though.'
                ]);
            }

            $pastEvents = Event::where('is_verified', true)->limit(2)->get();
            foreach ($pastEvents as $event) {
                $clubber->stampedEvents()->syncWithoutDetaching([$event->id => ['scanned_at' => now()]]);
            }
        }

        $this->command->info('Database seeded and checked for Underpass Barcelona.');
    }
}