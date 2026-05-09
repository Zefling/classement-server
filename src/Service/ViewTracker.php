<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to track views and prevent duplicates
 */
class ViewTracker
{
    private const CACHE_PREFIX = 'view_tracker_';
    private const VIEW_EXPIRATION = 3600; // 1 hour by default
    
    private bool $debug = false;

    public function __construct(
        private RequestStack $requestStack,
        private CacheItemPoolInterface $cache,
        private int $viewExpirationSeconds = self::VIEW_EXPIRATION
    ) {
    }

    /**
     * Checks if a view should be counted
     * 
     * @param string $rankingId The ranking identifier
     * @return bool True if the view should be counted, false otherwise
     */
    public function shouldCountView(string $rankingId): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            if ($this->debug) {
                error_log('[ViewTracker] No request, view counted by default');
            }
            return true; // No request, count by default
        }

        $identifier = $this->getUniqueIdentifier($request);
        $cacheKey = $this->getCacheKey($rankingId, $identifier);

        if ($this->debug) {
            error_log('[ViewTracker] RankingID: ' . $rankingId);
            error_log('[ViewTracker] Identifier: ' . $identifier);
            error_log('[ViewTracker] CacheKey: ' . $cacheKey);
        }

        try {
            // Check if the key exists in cache
            $item = $this->cache->getItem($cacheKey);
            
            if ($item->isHit()) {
                // Already viewed recently
                if ($this->debug) {
                    error_log('[ViewTracker] Cache HIT - View blocked');
                }
                return false;
            }

            // Mark as viewed
            $item->set(true);
            $item->expiresAfter($this->viewExpirationSeconds);
            $this->cache->save($item);
            
            if ($this->debug) {
                error_log('[ViewTracker] Cache MISS - View counted and marked');
            }
            
            return true;

        } catch (\Exception $e) {
            // In case of cache error, count the view for safety
            if ($this->debug) {
                error_log('[ViewTracker] Cache error: ' . $e->getMessage());
            }
            return true;
        }
    }

    /**
     * Generates a unique identifier based on session and IP
     */
    private function getUniqueIdentifier($request): string
    {
        $identifiers = [];

        // 1. Session ID (if available)
        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session && $session->isStarted()) {
            $identifiers[] = 'session:' . $session->getId();
        }

        // 2. Client IP
        $ip = $this->getClientIp($request);
        if ($ip) {
            $identifiers[] = 'ip:' . $ip;
        }

        // 3. User Agent (to differentiate devices)
        $userAgent = $request->headers->get('User-Agent');
        if ($userAgent) {
            // Hash the user agent to reduce size
            $identifiers[] = 'ua:' . substr(md5($userAgent), 0, 8);
        }

        // Combine all identifiers
        return md5(implode('|', $identifiers));
    }

    /**
     * Gets the real client IP (handles proxies)
     */
    private function getClientIp($request): ?string
    {
        // Check proxy headers
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipHeaders as $header) {
            $ip = $request->server->get($header);
            if ($ip) {
                // If X-Forwarded-For contains multiple IPs, take the first one
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate the IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $request->getClientIp();
    }

    /**
     * Generates the cache key
     */
    private function getCacheKey(string $rankingId, string $identifier): string
    {
        return self::CACHE_PREFIX . md5($rankingId . '_' . $identifier);
    }

    /**
     * Resets tracking for a ranking (useful for tests)
     */
    public function resetTracking(string $rankingId): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $identifier = $this->getUniqueIdentifier($request);
            $cacheKey = $this->getCacheKey($rankingId, $identifier);
            $this->cache->deleteItem($cacheKey);
        }
    }

    /**
     * Configures the view expiration duration
     */
    public function setViewExpiration(int $seconds): void
    {
        $this->viewExpirationSeconds = $seconds;
    }

    /**
     * Gets the current expiration duration
     */
    public function getViewExpiration(): int
    {
        return $this->viewExpirationSeconds;
    }
    
    /**
     * Enables/disables debug mode
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }
}
