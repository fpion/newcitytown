<?php

declare(strict_types=1);

namespace App\Domain\News\Event;

use App\Domain\Shared\DomainEvent;

final readonly class NewsPublished implements DomainEvent
{
    public function __construct(
        private string $aggregateId,
        public \DateTimeImmutable $publishedAt,
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
        return 'news.published';
    }

    public function payload(): array
    {
        return [
            'published_at' => $this->publishedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function fromPayload(string $aggregateId, array $payload, \DateTimeImmutable $occurredAt): static
    {
        return new self(
            aggregateId: $aggregateId,
            publishedAt: new \DateTimeImmutable($payload['published_at']),
            occurredAt: $occurredAt,
        );
    }
}
