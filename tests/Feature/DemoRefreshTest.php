<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

test('demo:refresh refuses to run when the demo flag is off', function () {
    config(['app.demo' => false]);

    $event = Event::factory()->create(['title' => 'Untouched Event']);

    $this->artisan('demo:refresh')->assertExitCode(1);

    assertDatabaseMissing('events', ['id' => 999999]); // sanity: table still queryable
    expect(Event::where('id', $event->id)->exists())->toBeTrue();
});

test('demo:refresh wipes and reseeds event data when the demo flag is on', function () {
    config(['app.demo' => true]);

    $staleEvent = Event::factory()->create([
        'title' => 'Stale Old Event',
        'date' => now()->subYear()->format('Y-m-d'),
    ]);

    $this->artisan('demo:refresh')->assertExitCode(0);

    expect(Event::where('title', 'Stale Old Event')->exists())->toBeFalse();
    expect(Event::where('date', '>', now()->format('Y-m-d'))->exists())->toBeTrue();
});

test('demo:refresh keeps the three named demo accounts after a run', function () {
    config(['app.demo' => true]);

    $this->artisan('demo:refresh')->assertExitCode(0);

    foreach (['admin@underpass.com', 'organizer@test.com', 'clubber@test.com'] as $email) {
        expect(User::where('email', $email)->exists())->toBeTrue();
    }
});
