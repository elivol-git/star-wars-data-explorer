<?php

namespace Tests\Feature\Commands;

use App\Jobs\FetchEntityImages;
use App\Models\Person;
use App\Models\Planet;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScrapeEntityImagesTest extends TestCase
{
    use DatabaseMigrations;

    public function test_command_scrapes_persons()
    {
        Queue::fake();

        // Create exactly 2 persons
        Person::factory()->create(['name' => 'Luke Skywalker']);
        Person::factory()->create(['name' => 'Leia Organa']);

        $this->artisan('scrape:entity-images')
            ->assertExitCode(0);

        // Should have dispatched at least 2 jobs for persons
        $this->assertGreaterThanOrEqual(2, Queue::pushed(FetchEntityImages::class)->count());
    }

    public function test_command_scrapes_planets()
    {
        Queue::fake();

        // Create exactly 1 planet
        Planet::factory()->create(['name' => 'Tatooine']);

        $this->artisan('scrape:entity-images')
            ->assertExitCode(0);

        // Verify a job was pushed
        $this->assertGreaterThanOrEqual(1, Queue::pushed(FetchEntityImages::class)->count());
    }

    public function test_command_dispatches_jobs_for_multiple_entities()
    {
        Queue::fake();

        // Create some persons
        foreach (range(1, 3) as $i) {
            Person::factory()->create(['name' => "Person $i"]);
        }

        $this->artisan('scrape:entity-images')
            ->assertExitCode(0);

        // Should have dispatched at least 3 jobs
        $this->assertGreaterThanOrEqual(3, Queue::pushed(FetchEntityImages::class)->count());
    }
}
