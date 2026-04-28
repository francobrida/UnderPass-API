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
use Illuminate\Support\Facades\Schema;

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
            'password' => 'password',
        ]);

        $organizer = User::factory()->create([
            'name' => 'Main Organizer',
            'email' => 'organizer@test.com',
            'role' => UserRole::ORGANIZER,
            'password' => 'password',
        ]);

        $clubber = User::factory()->create([
            'name' => 'Pepe Clubber',
            'email' => 'clubber@test.com',
            'role' => UserRole::CLUBBER,
            'password' =>  'password',
        ]);

        $crowd = User::factory(10)->create();

     
        for ($i = 0; $i < 8; $i++) {
            $event = Event::factory()->create([
                'user_id' => $organizer->id,
                'is_verified' => true,
            ]);

            $randomKeys = array_rand($allGenreIds, rand(1, 2));
            if (!is_array($randomKeys)) {
                $randomKeys = [$randomKeys];
            }

            $selectedIds = [];
            foreach ($randomKeys as $key) {
                $selectedIds[] = $allGenreIds[$key];
            }

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
            $clubber->stampedEvents()->attach($event->id, ['scanned_at' => now()]);
        }

        $this->command->info('Configurando Passport...');
        
        \Illuminate\Support\Facades\Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'UnderPass Personal Access Client',
            '--no-interaction' => true
        ]);

        $this->command->info('Database seeded for Underpass Barcelona.');
    }
}