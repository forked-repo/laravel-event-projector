<?php

namespace Spatie\EventProjector\Models;

use Illuminate\Session\Store;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\EventProjector\Projectors\Projector;
use Spatie\EventProjector\Facades\EventProjectionist;

class ProjectorStatus extends Model
{
    public $guarded = [];

    public static function getForProjector(Projector $projector, string $stream = 'main'): ProjectorStatus
    {
        return self::firstOrCreate([
            'projector_name' => $projector->getName(),
            'stream' => $stream,
        ]);
    }

    public static function getAllForProjector(Projector $projector): Collection
    {
        return static::where('projector_name', $projector->getName())->get();
    }

    public function rememberLastProcessedEvent(StoredEvent $storedEvent): ProjectorStatus
    {
        $this->last_processed_event_id = $storedEvent->id;
        $this->save();

        return $this;
    }

    public function hasReceivedAllEvents(): bool
    {
        $highestProcessedEventId = (int) self::query()
            ->where('projector_name', $this->getProjector()->getName())
            ->max('last_processed_event_id') ?? 0;

        return $highestProcessedEventId === StoredEvent::getMaxIdForProjector($this->getProjector());
    }

    public function getProjector(): Projector
    {
        return EventProjectionist::getProjector($this->projector_name);
    }
}
