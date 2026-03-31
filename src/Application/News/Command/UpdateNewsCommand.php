<?php

declare(strict_types=1);

namespace App\Application\News\Command;

final readonly class UpdateNewsCommand
{
    public function __construct(
        public string $newsId,
        public string $title,
        public string $content,
    ) {
    }
}
