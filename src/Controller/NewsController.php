<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\News\Command\ChangeNewsVisibilityCommand;
use App\Application\News\Command\CreateNewsCommand;
use App\Application\News\Command\PublishNewsCommand;
use App\Application\News\Command\UpdateNewsCommand;
use App\Infrastructure\Projection\News\NewsListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/news', name: 'news_')]
final class NewsController extends AbstractController
{
    public function __construct(
        private readonly NewsListRepository $newsListRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /** List all news. */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $newsList = $this->newsListRepository->findAll();

        return $this->render('news/index.html.twig', [
            'news_list' => $newsList,
        ]);
    }

    /** Show a single news detail. */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '[0-9a-f\-]{36}'])]
    public function show(string $id): Response
    {
        $news = $this->newsListRepository->findById($id);

        if ($news === null) {
            throw $this->createNotFoundException('News not found.');
        }

        return $this->render('news/show.html.twig', [
            'news' => $news,
        ]);
    }

    /** Show creation form and handle submission. */
    #[Route('/create', name: 'create', methods: ['GET', 'POST'], priority: 10)]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $title = trim((string) $request->request->get('title', ''));

            if ($title === '') {
                $this->addFlash('error', 'The title cannot be empty.');

                return $this->render('news/create.html.twig');
            }

            $private = !$request->request->getBoolean('public', false);
            $content = (string) $request->request->get('content', '');
            $this->messageBus->dispatch(new CreateNewsCommand(title: $title, private: $private, content: $content));

            $this->addFlash('success', 'News created successfully.');

            return $this->redirectToRoute('news_index');
        }

        return $this->render('news/create.html.twig');
    }

    /** Show edit form and handle submission. */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '[0-9a-f\-]{36}'])]
    public function edit(string $id, Request $request): Response
    {
        $news = $this->newsListRepository->findById($id);

        if ($news === null) {
            throw $this->createNotFoundException('News not found.');
        }

        if ($request->isMethod('POST')) {
            $title = trim((string) $request->request->get('title', ''));

            if ($title === '') {
                $this->addFlash('error', 'The title cannot be empty.');

                return $this->render('news/edit.html.twig', ['news' => $news]);
            }

            $content = (string) $request->request->get('content', '');

            try {
                $this->messageBus->dispatch(new UpdateNewsCommand(newsId: $id, title: $title, content: $content));
                $this->addFlash('success', 'News updated successfully.');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->render('news/edit.html.twig', ['news' => $news]);
            }

            return $this->redirectToRoute('news_show', ['id' => $id]);
        }

        return $this->render('news/edit.html.twig', ['news' => $news]);
    }

    /** Change the visibility of a news (public/private). */
    #[Route('/{id}/visibility', name: 'visibility', methods: ['POST'], requirements: ['id' => '[0-9a-f\-]{36}'])]
    public function changeVisibility(string $id, Request $request): Response
    {
        $news = $this->newsListRepository->findById($id);

        if ($news === null) {
            throw $this->createNotFoundException('News not found.');
        }

        $private = $request->request->getBoolean('private');

        try {
            $this->messageBus->dispatch(new ChangeNewsVisibilityCommand(newsId: $id, private: $private));
            $this->addFlash('success', $private ? 'News is now private.' : 'News is now public.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('news_show', ['id' => $id]);
    }

    /** Publish a news. */
    #[Route('/{id}/publish', name: 'publish', methods: ['POST'], requirements: ['id' => '[0-9a-f\-]{36}'])]
    public function publish(string $id): Response
    {
        $news = $this->newsListRepository->findById($id);

        if ($news === null) {
            throw $this->createNotFoundException('News not found.');
        }

        try {
            $this->messageBus->dispatch(new PublishNewsCommand(newsId: $id));
            $this->addFlash('success', 'News published successfully.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('news_show', ['id' => $id]);
    }
}
