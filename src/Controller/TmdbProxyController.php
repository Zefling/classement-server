<?php

namespace App\Controller;

use App\Controller\Common\TokenAuthenticatedController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TmdbProxyController extends AbstractController implements TokenAuthenticatedController
{
    public function __construct(
        private HttpClientInterface $tmdbClient,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/tmdb/{path}', name: 'tmdb_proxy', requirements: ['path' => '(search/movie|configuration/primary_translations)'], methods: ['GET'])]
    public function proxy(string $path, Request $request): JsonResponse
    {
        try {
            $response = $this->tmdbClient->request('GET', $path, [
                'query' => $request->query->all()
            ]);
            
            return new JsonResponse(
                $response->toArray(),
                $response->getStatusCode()
            );
        } catch (HttpExceptionInterface $e) {
            $response = $e->getResponse();
            $this->logger->error('TMDb API error', [
                'path' => $path,
                'status' => $response->getStatusCode()
            ]);
            return new JsonResponse(
                $response->toArray(false),
                $response->getStatusCode()
            );
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('TMDb API transport error', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return new JsonResponse(['error' => 'TMDb API unavailable'], 503);
        }
    }
}
