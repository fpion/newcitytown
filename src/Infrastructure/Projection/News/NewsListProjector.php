<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection\News;

use App\Domain\News\Event\NewsCreated;
use App\Domain\News\Event\NewsEdited;
use App\Domain\News\Event\NewsPublished;
use App\Domain\News\Event\NewsVisibilityChanged;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Projector that maintains the news_list_view read model.
 *
 * Each handler listens to domain events dispatched after EventStore append.
 */
final readonly class NewsListProjector
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[AsMessageHandler]
    public function onNewsCreated(NewsCreated $event): void
    {
        $this->connection->insert('news_list_view', [
            'id' => $event->aggregateId(),
            'title' => $event->title,
            'content' => $event->content,
            'created_at' => $event->createdAt->format('Y-m-d H:i:s'),
            'published' => false,
            'published_at' => null,
            'private' => $event->private,
        ]);
    }

    #[AsMessageHandler]
    public function onNewsPublished(NewsPublished $event): void
    {
        $this->connection->update('news_list_view', [
            'published' => true,
            'published_at' => $event->publishedAt->format('Y-m-d H:i:s'),
        ], [
            'id' => $event->aggregateId(),
        ]);
    }

    #[AsMessageHandler]
    public function onNewsVisibilityChanged(NewsVisibilityChanged $event): void
    {
        $this->connection->update('news_list_view', [
            'private' => $event->private,
        ], [
            'id' => $event->aggregateId(),
        ]);
    }

    #[AsMessageHandler]
    public function onNewsEdited(NewsEdited $event): void
    {
        $this->connection->update('news_list_view', [
            'title' => $event->title,
            'content' => $event->content,
        ], [
            'id' => $event->aggregateId(),
        ]);
    }
}
