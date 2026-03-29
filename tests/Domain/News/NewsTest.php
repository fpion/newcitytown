<?php

namespace App\Tests\Domain\News;

use App\Domain\News\News;

describe('Manage news', function () {
test('Should Create a news', function () {
    $now = new \DateTimeImmutable();
    $news = new News('This is a new News',$now);

    expect($news->getNewsInfo()->title)->toBe('This is a new News');
});

test('Should Can publish a news', function () {
    $now = new \DateTimeImmutable();
    $news = new News('This is a new News',$now);

    expect($news->getNewsInfo()->title)->toBe('This is a new News');
});
});
