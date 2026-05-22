<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AddMoreGenresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genres = [
            'Acid',
            'Alternative',
            'Ambient',
            'Breakbeat',
            'Chiptune',
            'Darkwave',
            'Deep House',
            'Disco',
            'Drum n Bass',
            'Dub',
            'Dubstep',
            'EDM',
            'Electro Pop',
            'Electro Rock',
            'Experimental',
            'Hard Techno',
            'House',
            'Hypnotic Raw Techno',
            'Indie Dance',
            'Industrial',
            'Melodic Techno',
            'Minimal',
            'Organic',
            'Others',
            'Peak Time Techno',
            'Progressive',
            'Psytrance',
            'Tech House',
            'Techno',
            'Trance'
        ];

        foreach ($genres as $name) {
            Genre::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }

        $this->command->info('Géneros adicionales agregados correctamente.');
    }
}
