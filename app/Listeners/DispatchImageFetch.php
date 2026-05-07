<?php

namespace App\Listeners;

use App\Jobs\FetchEntityImages;
use Illuminate\Database\Eloquent\Model;

class DispatchImageFetch
{
    public function handle(object $event): void
    {
        $entity = $event->model ?? $event->getModel() ?? null;

        if (!$entity instanceof Model) {
            return;
        }

        $type = class_basename($entity);
        $id = $entity->id;
        $name = $entity->name ?? '';

        if (!$name) {
            return;
        }

        FetchEntityImages::dispatch($type, $id, $name);
    }
}
