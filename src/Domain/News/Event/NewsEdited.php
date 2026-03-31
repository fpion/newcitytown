<?php

declare(strict_types=1);

namespace App\Domain\News\Event;

use App\Domain\Shared\DomainEvent;

final readonly class NewsEdited implements DomainEvent
{
    public function __construct(
        private string $aggregateId,
        public string $title,
        public string $content,
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
        return 'news.edited';
    }

    public function payload(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }

    public static function fromPayload(string $aggregateId, array $payload, \DateTimeImmutable $occurredAt): static
    {
        return new self(
            aggregateId: $aggregateId,
            title: $payload['title'],
            content: $payload['content'],
            occurredAt: $occurredAt,
        );
    }
}
