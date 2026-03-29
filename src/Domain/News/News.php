<?php

namespace App\Domain\News;

class News
{
    private string $title;
    private \DateTimeImmutable $created_at;

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
}
