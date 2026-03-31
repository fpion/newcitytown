<?php

declare(strict_types=1);

namespace App\Application\News\Command;

final readonly class ChangeNewsVisibilityCommand
{
    public function __construct(
        public string $newsId,
        public bool $private,
    ) {
    }
}
