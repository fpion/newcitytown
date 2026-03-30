<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection\News;

use App\Domain\News\Event\NewsCreated;
use App\Domain\News\Event\NewsPublished;
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
            'created_at' => $event->createdAt->format('Y-m-d H:i:s'),
            'published' => false,
            'published_at' => null,
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
}
