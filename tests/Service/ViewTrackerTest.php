<?php

namespace App\Tests\Service;

use App\Service\ViewTracker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ViewTrackerTest extends KernelTestCase
{
    private ViewTracker $viewTracker;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->viewTracker = $container->get(ViewTracker::class);
        $this->requestStack = $container->get(RequestStack::class);
    }

    public function testShouldCountViewFirstTime(): void
    {
        $rankingId = 'test-ranking-' . uniqid();
        
        // Create a simulated request
        $request = Request::create('/api/classements/' . $rankingId);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->requestStack->push($request);
        
        // First view should be counted
        $this->assertTrue($this->viewTracker->shouldCountView($rankingId));
    }

    public function testShouldNotCountViewSecondTime(): void
    {
        $rankingId = 'test-ranking-' . uniqid();
        
        // Create a simulated request
        $request = Request::create('/api/classements/' . $rankingId);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->requestStack->push($request);
        
        // First view
        $this->assertTrue($this->viewTracker->shouldCountView($rankingId));
        
        // Second immediate view should not be counted
        $this->assertFalse($this->viewTracker->shouldCountView($rankingId));
    }

    public function testDifferentIpsShouldCountSeparately(): void
    {
        $rankingId = 'test-ranking-' . uniqid();
        
        // First IP
        $request1 = Request::create('/api/classements/' . $rankingId);
        $request1->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->requestStack->push($request1);
        
        $this->assertTrue($this->viewTracker->shouldCountView($rankingId));
        
        // Second different IP
        $this->requestStack->pop();
        $request2 = Request::create('/api/classements/' . $rankingId);
        $request2->server->set('REMOTE_ADDR', '192.168.1.2');
        $this->requestStack->push($request2);
        
        $this->assertTrue($this->viewTracker->shouldCountView($rankingId));
    }

    public function testDifferentRankingsShouldCountSeparately(): void
    {
        $rankingId1 = 'test-ranking-1-' . uniqid();
        $rankingId2 = 'test-ranking-2-' . uniqid();
        
        $request = Request::create('/api/classements/test');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->requestStack->push($request);
        
        // First view of ranking 1
        $this->assertTrue($this->viewTracker->shouldCountView($rankingId1));
        
        // First view of ranking 2 (different)
        $this->assertTrue($this->viewTracker->shouldCountView($rankingId2));
        
        // Second view of ranking 1
        $this->assertFalse($this->viewTracker->shouldCountView($rankingId1));
    }

    public function testResetTracking(): void
    {
        $rankingId = 'test-ranking-' . uniqid();
        
        $request = Request::create('/api/classements/' . $rankingId);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->requestStack->push($request);
        
        // First view
        $this->assertTrue($this->viewTracker->shouldCountView($rankingId));
        
        // Second view should not count
        $this->assertFalse($this->viewTracker->shouldCountView($rankingId));
        
        // Reset
        $this->viewTracker->resetTracking($rankingId);
        
        // After reset, should count again
        $this->assertTrue($this->viewTracker->shouldCountView($rankingId));
    }

    public function testViewExpirationConfiguration(): void
    {
        // Check default value
        $this->assertEquals(3600, $this->viewTracker->getViewExpiration());
        
        // Change value
        $this->viewTracker->setViewExpiration(7200);
        $this->assertEquals(7200, $this->viewTracker->getViewExpiration());
    }

    public function testProxyIpHandling(): void
    {
        $rankingId = 'test-ranking-' . uniqid();
        
        // Simulate a request via proxy (X-Forwarded-For)
        $request = Request::create('/api/classements/' . $rankingId);
        $request->server->set('HTTP_X_FORWARDED_FOR', '203.0.113.1, 198.51.100.1');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->requestStack->push($request);
        
        // Should use the first IP from X-Forwarded-For
        $this->assertTrue($this->viewTracker->shouldCountView($rankingId));
        $this->assertFalse($this->viewTracker->shouldCountView($rankingId));
    }

    public function testCloudflareIpHandling(): void
    {
        $rankingId = 'test-ranking-' . uniqid();
        
        // Simulate a request via Cloudflare
        $request = Request::create('/api/classements/' . $rankingId);
        $request->server->set('HTTP_CF_CONNECTING_IP', '203.0.113.1');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->requestStack->push($request);
        
        // Should use the Cloudflare IP
        $this->assertTrue($this->viewTracker->shouldCountView($rankingId));
        $this->assertFalse($this->viewTracker->shouldCountView($rankingId));
    }

    protected function tearDown(): void
    {
        // Clean up the request stack
        while ($this->requestStack->getCurrentRequest()) {
            $this->requestStack->pop();
        }
        
        parent::tearDown();
    }
}
