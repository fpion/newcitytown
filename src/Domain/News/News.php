<?php

declare(strict_types=1);

namespace App\Domain\News;

use App\Domain\News\Event\NewsCreated;
use App\Domain\News\Event\NewsPublished;
use App\Domain\News\Event\NewsVisibilityChanged;
use App\Domain\Shared\AggregateRoot;

final class News extends AggregateRoot
{
    private NewsId $id;
    private string $title;
    private \DateTimeImmutable $createdAt;
    private bool $published = false;
    private ?\DateTimeImmutable $publishedAt = null;
    private bool $private = true;

    /** Named constructor — the ONLY way to create a new News. */
    public static function create(NewsId $id, string $title, \DateTimeImmutable $createdAt, bool $private = true): self
    {
        $news = new self();

        $news->recordThat(new NewsCreated(
            aggregateId: (string) $id,
            title: $title,
            createdAt: $createdAt,
            occurredAt: new \DateTimeImmutable(),
            private: $private,
        ));

        return $news;
    }

    public function publish(): void
    {
        if ($this->published) {
            throw new \DomainException('News is already published.');
        }

        $this->recordThat(new NewsPublished(
            aggregateId: (string) $this->id,
            publishedAt: new \DateTimeImmutable(),
            occurredAt: new \DateTimeImmutable(),
        ));
    }

    public function aggregateId(): string
    {
        return (string) $this->id;
    }

    public function titleInfo(): TitleInfo
    {
        return new TitleInfo(title: $this->title, createdAt: $this->createdAt);
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function makePrivate(): void
    {
        if ($this->private) {
            throw new \DomainException('News is already private.');
        }

        $this->recordThat(new NewsVisibilityChanged(
            aggregateId: (string) $this->id,
            private: true,
            occurredAt: new \DateTimeImmutable(),
        ));
    }

    public function makePublic(): void
    {
        if (!$this->private) {
            throw new \DomainException('News is already public.');
        }

        $this->recordThat(new NewsVisibilityChanged(
            aggregateId: (string) $this->id,
            private: false,
            occurredAt: new \DateTimeImmutable(),
        ));
    }

    // --- Event application methods (private, pure state mutation) ---

    protected function applyNewsCreated(NewsCreated $event): void
    {
        $this->id = NewsId::fromString($event->aggregateId());
        $this->title = $event->title;
        $this->createdAt = $event->createdAt;
        $this->private = $event->private;
    }

    protected function applyNewsPublished(NewsPublished $event): void
    {
        $this->published = true;
        $this->publishedAt = $event->publishedAt;
    }

    protected function applyNewsVisibilityChanged(NewsVisibilityChanged $event): void
    {
        $this->private = $event->private;
    }

}
