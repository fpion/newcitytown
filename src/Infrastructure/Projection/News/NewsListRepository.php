<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection\News;

use Doctrine\DBAL\Connection;

/** Read-only query repository. Used by controllers/API, never by aggregates. */
final readonly class NewsListRepository
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /** @return list<NewsListReadModel> */
    public function findAllPublished(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM news_list_view WHERE published = true ORDER BY published_at DESC',
        );

        return array_map($this->hydrate(...), $rows);
    }

    /** @return list<NewsListReadModel> */
    public function findAll(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM news_list_view ORDER BY created_at DESC',
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function findById(string $id): ?NewsListReadModel
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM news_list_view WHERE id = ?',
            [$id],
        );

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): NewsListReadModel
    {
        return new NewsListReadModel(
            id: $row['id'],
            title: $row['title'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            published: (bool) $row['published'],
            publishedAt: $row['published_at'] ? new \DateTimeImmutable($row['published_at']) : null,
            private: (bool) $row['private'],
        );
    }
}
