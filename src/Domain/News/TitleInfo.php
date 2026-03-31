<?php

declare(strict_types=1);

namespace App\Domain\News;

final readonly class TitleInfo
{
    public function __construct(
        public string $title,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
