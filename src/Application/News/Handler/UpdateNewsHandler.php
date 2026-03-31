<?php

declare(strict_types=1);

namespace App\Application\News\Handler;

use App\Application\News\Command\UpdateNewsCommand;
use App\Domain\News\News;
use App\Infrastructure\EventStore\EventStoreInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class UpdateNewsHandler
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(UpdateNewsCommand $command): void
    {
        $stream = $this->eventStore->load($command->newsId);
        $news = News::reconstituteFrom($stream);

        $versionBeforeChange = $news->version();

        $news->edit($command->title, $command->content);

        $newEvents = $news->uncommittedEvents();

        $this->eventStore->append(
            aggregateId: $news->aggregateId(),
            events: $newEvents,
            expectedVersion: $versionBeforeChange,
        );

        foreach ($newEvents as $event) {
            $this->eventBus->dispatch($event);
        }

        $news->clearUncommittedEvents();
    }
}
