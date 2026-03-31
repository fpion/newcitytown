<?php

declare(strict_types=1);

namespace App\Application\News\Handler;

use App\Application\News\Command\CreateNewsCommand;
use App\Domain\News\News;
use App\Domain\News\NewsId;
use App\Infrastructure\EventStore\EventStoreInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class CreateNewsHandler
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(CreateNewsCommand $command): string
    {
        $id = NewsId::generate();

        $news = News::create(
            id: $id,
            title: $command->title,
            createdAt: new \DateTimeImmutable(),
            private: $command->private,
        );

        $events = $news->uncommittedEvents();

        $this->eventStore->append(
            aggregateId: $news->aggregateId(),
            events: $events,
            expectedVersion: 0,
        );

        // Dispatch events so projectors update read models
        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }

        $news->clearUncommittedEvents();

        return (string) $id;
    }
}
