<?php

declare(strict_types=1);

namespace App\Domain\Shared;

abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $uncommittedEvents = [];
    private int $version = 0;

    /** Record an event and immediately apply it to mutate state. */
    protected function recordThat(DomainEvent $event): void
    {
        $this->apply($event);
        $this->uncommittedEvents[] = $event;
    }

    /** Apply a single event to mutate aggregate state via applyXxx convention. */
    private function apply(DomainEvent $event): void
    {
        $method = 'apply' . (new \ReflectionClass($event))->getShortName();

        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException(
                sprintf('Missing apply method "%s" in %s.', $method, static::class),
            );
        }

        $this->$method($event);
        $this->version++;
    }

    /** Reconstitute aggregate from a stream of historical events. */
    public static function reconstituteFrom(EventStream $stream): static
    {
        $instance = new static();

        foreach ($stream as $event) {
            $instance->apply($event);
        }

        return $instance;
    }

    /** @return list<DomainEvent> */
    public function uncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    public function clearUncommittedEvents(): void
    {
        $this->uncommittedEvents = [];
    }

    public function version(): int
    {
        return $this->version;
    }

    abstract public function aggregateId(): string;
}
