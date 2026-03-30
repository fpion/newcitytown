<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore;

use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\EventStream;
use Doctrine\DBAL\Connection;

final readonly class DoctrineEventStore implements EventStoreInterface
{
    /** @param array<string, class-string<DomainEvent>> $eventClassMap */
    public function __construct(
        private Connection $connection,
        private array $eventClassMap,
    ) {
    }

    public function append(string $aggregateId, array $events, int $expectedVersion): void
    {
        $this->connection->transactional(function () use ($aggregateId, $events, $expectedVersion): void {
            $currentVersion = (int) $this->connection->fetchOne(
                'SELECT COALESCE(MAX(version), 0) FROM event_store WHERE aggregate_id = ?',
                [$aggregateId],
            );

            if ($currentVersion !== $expectedVersion) {
                throw new \RuntimeException(sprintf(
                    'Concurrency conflict: expected version %d, found %d.',
                    $expectedVersion,
                    $currentVersion,
                ));
            }

            $version = $expectedVersion;

            foreach ($events as $event) {
                $version++;
                $this->connection->insert('event_store', [
                    'aggregate_id' => $event->aggregateId(),
                    'event_name' => $event->eventName(),
                    'payload' => json_encode($event->payload(), JSON_THROW_ON_ERROR),
                    'occurred_at' => $event->occurredAt()->format('Y-m-d H:i:s.u'),
                    'version' => $version,
                ]);
            }
        });
    }

    public function load(string $aggregateId): EventStream
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM event_store WHERE aggregate_id = ? ORDER BY version ASC',
            [$aggregateId],
        );

        $events = array_map(function (array $row): DomainEvent {
            $class = $this->eventClassMap[$row['event_name']]
                ?? throw new \RuntimeException("Unknown event: {$row['event_name']}");

            return $class::fromPayload(
                aggregateId: $row['aggregate_id'],
                payload: json_decode($row['payload'], true, flags: JSON_THROW_ON_ERROR),
                occurredAt: new \DateTimeImmutable($row['occurred_at']),
            );
        }, $rows);

        return new EventStream($events);
    }
}
