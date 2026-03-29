<?php

namespace App\Domain\News;

class TitleInfo
{
    public string $title;
    public \DateTimeImmutable $createdAt;

    public function __construct(string $title, \DateTimeImmutable $createdAt)
    {
        $this->title = $title;
        $this->createdAt = $createdAt;
    }
}
