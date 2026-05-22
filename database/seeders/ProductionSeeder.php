<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AddMoreGenresSeeder::class,
        ]);

        $admin = User::where('email', 'francobrida@underpass.com')->first();
        if (!$admin) {
            User::factory()->create([
                'name' => 'Franco Brida',
                'email' => 'francobrida@underpass.com',
                'password' => '1234underpass',
                'role' => UserRole::ADMIN,
                'points' => 10,
            ]);
        }

        $this->command->info('Database fully seeded for PRODUCTION (Admin + Genres only).');
    }
}
