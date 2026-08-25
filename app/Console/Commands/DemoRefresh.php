<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoRefresh extends Command
{
    protected $signature = 'demo:refresh';

    protected $description = 'Demo-only: wipe and reseed event/user data so demo dates never go stale';

    public function handle(): int
    {
        if (! config('app.demo')) {
            $this->error('Aborted: this deployment does not have APP_DEMO set. demo:refresh only runs when config(\'app.demo\') is true.');

            return self::FAILURE;
        }

        DB::transaction(function () {
            DB::table('oauth_access_tokens')->delete();

            User::whereNotIn('email', [
                'admin@underpass.com',
                'organizer@test.com',
                'clubber@test.com',
            ])->delete();

            Event::query()->delete();

            $this->call('db:seed', ['--force' => true]);
        });

        $this->info('Demo data refreshed.');

        return self::SUCCESS;
    }
}
