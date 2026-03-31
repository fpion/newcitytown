<?php

declare(strict_types=1);

namespace App\Domain\News\Event;

use App\Domain\Shared\DomainEvent;

final readonly class NewsVisibilityChanged implements DomainEvent
{
    public function __construct(
        private string $aggregateId,
        public bool $private,
        private \DateTimeImmutable $occurredAt,
    ) {
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'news.visibility_changed';
    }

    public function payload(): array
    {
        return [
            'private' => $this->private,
        ];
    }

    public static function fromPayload(string $aggregateId, array $payload, \DateTimeImmutable $occurredAt): static
    {
        return new self(
            aggregateId: $aggregateId,
            private: (bool) $payload['private'],
            occurredAt: $occurredAt,
        );
    }
}
