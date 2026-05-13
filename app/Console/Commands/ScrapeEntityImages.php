<?php

namespace App\Console\Commands;

use App\Jobs\FetchEntityImages;
use App\Models\Film;
use App\Models\Person;
use App\Models\Planet;
use App\Models\Species;
use App\Models\Starship;
use App\Models\Vehicle;
use Illuminate\Console\Command;

class ScrapeEntityImages extends Command
{
    protected $signature = 'scrape:entity-images {--delay=250}';
    protected $description = 'Scrape images for all entities from Pexels API';

    public function handle(): int
    {
        $delay = (int) $this->option('delay');

        $entities = [
            'Person' => Person::query(),
            'Planet' => Planet::query(),
            'Film' => Film::query(),
            'Starship' => Starship::query(),
            'Vehicle' => Vehicle::query(),
            'Species' => Species::query(),
        ];

        $totalDispatched = 0;

        foreach ($entities as $type => $query) {
            $count = $query->count();
            if ($count === 0) {
                continue;
            }

            $this->info("Processing $count $type(s)...");

            foreach ($query->cursor() as $entity) {
                $entityName = $entity->name ?? $entity->title ?? 'Unknown';
                FetchEntityImages::dispatch($type, $entity->id, $entityName)
                    ->delay(now()->addMilliseconds($totalDispatched * $delay));

                $totalDispatched++;
            }
        }

        $this->info("Dispatched $totalDispatched image fetch jobs");
        return 0;
    }
}
