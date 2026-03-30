<?php

declare(strict_types=1);

namespace App\Domain\News;

use Symfony\Component\Uid\Uuid;

final readonly class NewsId implements \Stringable
{
    private function __construct(
        private Uuid $id,
    ) {
    }

    public static function generate(): self
    {
        return new self(Uuid::v7());
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
