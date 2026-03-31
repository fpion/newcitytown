<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection\News;

/** Represents a row in the news_list_view read model table. */
final class NewsListReadModel
{
    public function __construct(
        public readonly string $id,
        public string $title,
        public \DateTimeImmutable $createdAt,
        public bool $published = false,
        public ?\DateTimeImmutable $publishedAt = null,
        public bool $private = true,
    ) {
    }
}
