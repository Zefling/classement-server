<?php

namespace App\Tests;

use App\Entity\ClassementStats;
use App\Repository\ClassementStatsRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClassementStatsTest extends KernelTestCase
{
    private ClassementStatsRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()
            ->get('doctrine')
            ->getRepository(ClassementStats::class);
    }

    public function testIncrementViewCount(): void
    {
        $rankingId = 'test-ranking-' . uniqid();
        
        // First increment
        $this->repository->incrementViewCount($rankingId);
        $count = $this->repository->getViewCount($rankingId);
        $this->assertEquals(1, $count);
        
        // Second increment
        $this->repository->incrementViewCount($rankingId);
        $count = $this->repository->getViewCount($rankingId);
        $this->assertEquals(2, $count);
        
        // Third increment
        $this->repository->incrementViewCount($rankingId);
        $count = $this->repository->getViewCount($rankingId);
        $this->assertEquals(3, $count);
    }

    public function testGetViewCountForNonExistentRanking(): void
    {
        $rankingId = 'non-existent-ranking-' . uniqid();
        $count = $this->repository->getViewCount($rankingId);
        $this->assertEquals(0, $count);
    }

    public function testGetViewCounts(): void
    {
        $rankingId1 = 'test-ranking-1-' . uniqid();
        $rankingId2 = 'test-ranking-2-' . uniqid();
        $rankingId3 = 'test-ranking-3-' . uniqid();
        
        // Increment different amounts
        $this->repository->incrementViewCount($rankingId1);
        $this->repository->incrementViewCount($rankingId1);
        
        $this->repository->incrementViewCount($rankingId2);
        $this->repository->incrementViewCount($rankingId2);
        $this->repository->incrementViewCount($rankingId2);
        
        // Get counts
        $counts = $this->repository->getViewCounts([$rankingId1, $rankingId2, $rankingId3]);
        
        $this->assertEquals(2, $counts[$rankingId1]);
        $this->assertEquals(3, $counts[$rankingId2]);
        $this->assertArrayNotHasKey($rankingId3, $counts);
    }

    public function testGetTopViewed(): void
    {
        $rankingIds = [];
        
        // Create rankings with different view counts
        for ($i = 1; $i <= 5; $i++) {
            $rankingId = 'top-ranking-' . $i . '-' . uniqid();
            $rankingIds[] = $rankingId;
            
            for ($j = 0; $j < $i; $j++) {
                $this->repository->incrementViewCount($rankingId);
            }
        }
        
        // Get top 3
        $topRankings = $this->repository->getTopViewed(3);
        
        $this->assertCount(3, $topRankings);
        $this->assertGreaterThanOrEqual(
            $topRankings[1]->getViewCount(),
            $topRankings[0]->getViewCount()
        );
        $this->assertGreaterThanOrEqual(
            $topRankings[2]->getViewCount(),
            $topRankings[1]->getViewCount()
        );
    }
}
