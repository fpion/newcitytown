<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore;

use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\EventStream;

interface EventStoreInterface
{
    /** @param list<DomainEvent> $events */
    public function append(string $aggregateId, array $events, int $expectedVersion): void;

    public function load(string $aggregateId): EventStream;
}
