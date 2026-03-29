<?php

namespace App\Domain\News;

use phpDocumentor\Reflection\Types\Boolean;

class News
{
    private string $title;
    private \DateTimeImmutable $created_at;
    private bool $publish = false;

    /**
     * @param string $string
     */
    public function __construct(string $string,\DateTimeImmutable $date)
    {
        $this->title = $string;
        $this->created_at = $date;

    }

    public function getNewsInfo(): TitleInfo
    {
        return new TitleInfo($this->title,$this->created_at);
    }

    public function isPublish(): bool
    {
        return $this->publish;
    }

    public function Publish(): void
    {
        $this->publish = true;
    }
}
