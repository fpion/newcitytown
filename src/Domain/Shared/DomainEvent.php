<?php

declare(strict_types=1);

namespace App\Domain\Shared;

interface DomainEvent
{
    public function aggregateId(): string;

    public function occurredAt(): \DateTimeImmutable;

    public function eventName(): string;

    /** @return array<string, mixed> */
    public function payload(): array;

    /** @param array<string, mixed> $payload */
    public static function fromPayload(string $aggregateId, array $payload, \DateTimeImmutable $occurredAt): static;
}
