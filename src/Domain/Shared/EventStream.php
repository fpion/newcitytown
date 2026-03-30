<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** @implements \IteratorAggregate<int, DomainEvent> */
final readonly class EventStream implements \IteratorAggregate, \Countable
{
    /** @param list<DomainEvent> $events */
    public function __construct(
        private array $events,
    ) {
    }

    /** @return \ArrayIterator<int, DomainEvent> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->events);
    }

    public function count(): int
    {
        return count($this->events);
    }
}
