<?php

namespace App\Controller;

use App\Controller\Common\TokenAuthenticatedController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TmdbProxyController extends AbstractController implements TokenAuthenticatedController
{
    public function __construct(
        private HttpClientInterface $tmdbClient,
        private LoggerInterface $logger,
        private CacheInterface $cache
    ) {
    }

    #[Route('/api/tmdb/{path}', name: 'tmdb_proxy', requirements: ['path' => '(search/movie|configuration/primary_translations)'], methods: ['GET'])]
    public function proxy(string $path, Request $request): JsonResponse
    {
        // Cache for primary_translations (1 day)
        if ($path === 'configuration/primary_translations') {
            try {
                $data = $this->cache->get('tmdb.primary_translations', function (ItemInterface $item) use ($path, $request) {
                    $item->expiresAfter(86400); // 24 hours
                    
                    $response = $this->tmdbClient->request('GET', $path, [
                        'query' => $request->query->all()
                    ]);
                    
                    return [
                        'data' => $response->toArray(),
                        'status' => $response->getStatusCode()
                    ];
                });
                
                // Fallback: if cache returns null (e.g. in tests with unconfigured mocks),
                // perform the request directly without caching.
                if ($data === null) {
                    $response = $this->tmdbClient->request('GET', $path, [
                        'query' => $request->query->all()
                    ]);
                    return new JsonResponse($response->toArray(), $response->getStatusCode());
                }

                return new JsonResponse($data['data'], $data['status']);
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
        
        // No cache for other endpoints
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
