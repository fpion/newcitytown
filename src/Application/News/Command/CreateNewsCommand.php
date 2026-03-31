<?php

declare(strict_types=1);

namespace App\Application\News\Command;

final readonly class CreateNewsCommand
{
    public function __construct(
        public string $title,
        public bool $private = true,
        public string $content = '',
    ) {
    }
}
