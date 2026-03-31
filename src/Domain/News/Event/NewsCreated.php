<?php

declare(strict_types=1);

namespace App\Domain\News\Event;

use App\Domain\Shared\DomainEvent;

final readonly class NewsCreated implements DomainEvent
{
    public function __construct(
        private string $aggregateId,
        public string $title,
        public \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $occurredAt,
        public bool $private = true,
        public string $content = '',
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
        return 'news.created';
    }

    public function payload(): array
    {
        return [
            'title' => $this->title,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'private' => $this->private,
            'content' => $this->content,
        ];
    }

    public static function fromPayload(string $aggregateId, array $payload, \DateTimeImmutable $occurredAt): static
    {
        return new self(
            aggregateId: $aggregateId,
            title: $payload['title'],
            createdAt: new \DateTimeImmutable($payload['created_at']),
            occurredAt: $occurredAt,
            private: $payload['private'] ?? true,
            content: $payload['content'] ?? '',
        );
    }
}
