<?php

declare(strict_types=1);

namespace Tests\Domain\News;

use App\Domain\News\Event\NewsCreated;
use App\Domain\News\Event\NewsPublished;
use App\Domain\News\News;
use App\Domain\News\NewsId;
use App\Domain\Shared\EventStream;

describe('News Aggregate Root', function () {

    test('should create a news and emit NewsCreated event', function () {
        $id = NewsId::generate();
        $now = new \DateTimeImmutable();

        $news = News::create(id: $id, title: 'Breaking news', createdAt: $now);

        expect($news->aggregateId())->toBe((string) $id)
            ->and($news->titleInfo()->title)->toBe('Breaking news')
            ->and($news->titleInfo()->createdAt)->toBe($now)
            ->and($news->isPublished())->toBeFalse()
            ->and($news->version())->toBe(1);

        // Verify the emitted event
        $events = $news->uncommittedEvents();
        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(NewsCreated::class)
            ->and($events[0]->title)->toBe('Breaking news')
            ->and($events[0]->aggregateId())->toBe((string) $id)
            ->and($events[0]->eventName())->toBe('news.created');
    });

    test('should publish a news and emit NewsPublished event', function () {
        $id = NewsId::generate();
        $news = News::create(id: $id, title: 'My news', createdAt: new \DateTimeImmutable());

        $news->clearUncommittedEvents(); // clear creation event
        $news->publish();

        expect($news->isPublished())->toBeTrue()
            ->and($news->version())->toBe(2);

        $events = $news->uncommittedEvents();
        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(NewsPublished::class)
            ->and($events[0]->eventName())->toBe('news.published');
    });

    test('should not publish twice', function () {
        $news = News::create(
            id: NewsId::generate(),
            title: 'My news',
            createdAt: new \DateTimeImmutable(),
        );

        $news->publish();

        expect(fn () => $news->publish())
            ->toThrow(\DomainException::class, 'News is already published.');
    });

    test('should reconstitute from event stream', function () {
        $id = NewsId::generate();
        $now = new \DateTimeImmutable();

        // Simulate stored events
        $stream = new EventStream([
            NewsCreated::fromPayload(
                aggregateId: (string) $id,
                payload: ['title' => 'Reconstituted news', 'created_at' => $now->format(\DateTimeInterface::ATOM)],
                occurredAt: $now,
            ),
            NewsPublished::fromPayload(
                aggregateId: (string) $id,
                payload: ['published_at' => $now->format(\DateTimeInterface::ATOM)],
                occurredAt: $now,
            ),
        ]);

        $news = News::reconstituteFrom($stream);

        expect($news->aggregateId())->toBe((string) $id)
            ->and($news->titleInfo()->title)->toBe('Reconstituted news')
            ->and($news->isPublished())->toBeTrue()
            ->and($news->version())->toBe(2)
            ->and($news->uncommittedEvents())->toBeEmpty(); // reconstitution does not produce uncommitted events
    });

});

describe('NewsId Value Object', function () {

    test('should generate a unique id', function () {
        $id1 = NewsId::generate();
        $id2 = NewsId::generate();

        expect((string) $id1)->not->toBe((string) $id2);
    });

    test('should reconstitute from string', function () {
        $id = NewsId::generate();
        $reconstructed = NewsId::fromString((string) $id);

        expect($id->equals($reconstructed))->toBeTrue();
    });

});

describe('NewsCreated Event', function () {

    test('should serialize and deserialize via payload', function () {
        $id = (string) NewsId::generate();
        $now = new \DateTimeImmutable();

        $event = new NewsCreated(
            aggregateId: $id,
            title: 'Test title',
            createdAt: $now,
            occurredAt: $now,
        );

        $restored = NewsCreated::fromPayload(
            aggregateId: $id,
            payload: $event->payload(),
            occurredAt: $now,
        );

        expect($restored->aggregateId())->toBe($id)
            ->and($restored->title)->toBe('Test title')
            ->and($restored->eventName())->toBe('news.created');
    });

});
