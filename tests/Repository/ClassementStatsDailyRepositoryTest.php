<?php

namespace App\Tests\Repository;

use App\Entity\ClassementStatsDaily;
use App\Repository\ClassementStatsDailyRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClassementStatsDailyRepositoryTest extends KernelTestCase
{
    private ?ClassementStatsDailyRepository $repository = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(ClassementStatsDailyRepository::class);
        
        // Clean up test data
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->createQuery('DELETE FROM App\Entity\ClassementStatsDaily s WHERE s.rankingId LIKE :prefix')
            ->setParameter('prefix', 'test_%')
            ->execute();
    }

    public function testIncrementViewCount(): void
    {
        $rankingId = 'test_ranking_' . uniqid();
        $date = new \DateTime('2026-05-08');

        // First increment
        $this->repository->incrementViewCount($rankingId, $date);
        $count = $this->repository->getViewCount($rankingId, $date);
        $this->assertEquals(1, $count);

        // Second increment
        $this->repository->incrementViewCount($rankingId, $date);
        $count = $this->repository->getViewCount($rankingId, $date);
        $this->assertEquals(2, $count);
    }

    public function testGetViewCountForNonExistentRanking(): void
    {
        $count = $this->repository->getViewCount('non_existent', new \DateTime());
        $this->assertEquals(0, $count);
    }

    public function testGetViewCountsForPeriod(): void
    {
        $rankingId = 'test_ranking_' . uniqid();
        
        // Create stats for multiple days
        $this->repository->incrementViewCount($rankingId, new \DateTime('2026-05-01'));
        $this->repository->incrementViewCount($rankingId, new \DateTime('2026-05-01'));
        $this->repository->incrementViewCount($rankingId, new \DateTime('2026-05-03'));
        $this->repository->incrementViewCount($rankingId, new \DateTime('2026-05-05'));

        // Get counts for period
        $counts = $this->repository->getViewCountsForPeriod(
            $rankingId,
            new \DateTime('2026-05-01'),
            new \DateTime('2026-05-05')
        );

        $this->assertCount(3, $counts);
        $this->assertEquals(2, $counts['2026-05-01']);
        $this->assertEquals(1, $counts['2026-05-03']);
        $this->assertEquals(1, $counts['2026-05-05']);
    }

    public function testGetTopViewedForDate(): void
    {
        $date = new \DateTime('2026-05-08');
        
        // Create stats for multiple rankings
        $ranking1 = 'test_ranking_' . uniqid();
        $ranking2 = 'test_ranking_' . uniqid();
        $ranking3 = 'test_ranking_' . uniqid();

        $this->repository->incrementViewCount($ranking1, $date);
        $this->repository->incrementViewCount($ranking1, $date);
        $this->repository->incrementViewCount($ranking1, $date);
        
        $this->repository->incrementViewCount($ranking2, $date);
        $this->repository->incrementViewCount($ranking2, $date);
        
        $this->repository->incrementViewCount($ranking3, $date);

        // Get top viewed
        $topViewed = $this->repository->getTopViewedForDate($date, 10);

        $this->assertGreaterThanOrEqual(3, count($topViewed));
        $this->assertEquals(3, $topViewed[0]->getViewCount());
        $this->assertEquals($ranking1, $topViewed[0]->getRankingId());
    }

    public function testGetTotalViewCount(): void
    {
        $rankingId = 'test_ranking_' . uniqid();
        
        // Create stats for multiple days
        $this->repository->incrementViewCount($rankingId, new \DateTime('2026-05-01'));
        $this->repository->incrementViewCount($rankingId, new \DateTime('2026-05-01'));
        $this->repository->incrementViewCount($rankingId, new \DateTime('2026-05-02'));
        $this->repository->incrementViewCount($rankingId, new \DateTime('2026-05-03'));

        $total = $this->repository->getTotalViewCount($rankingId);
        $this->assertEquals(4, $total);
    }

    public function testGetViewCountsForDate(): void
    {
        $date = new \DateTime('2026-05-08');
        $ranking1 = 'test_ranking_' . uniqid();
        $ranking2 = 'test_ranking_' . uniqid();

        $this->repository->incrementViewCount($ranking1, $date);
        $this->repository->incrementViewCount($ranking1, $date);
        $this->repository->incrementViewCount($ranking2, $date);

        $counts = $this->repository->getViewCountsForDate([$ranking1, $ranking2], $date);

        $this->assertCount(2, $counts);
        $this->assertEquals(2, $counts[$ranking1]);
        $this->assertEquals(1, $counts[$ranking2]);
    }

    public function testDateNormalization(): void
    {
        $rankingId = 'test_ranking_' . uniqid();
        
        // Different times on the same day should be normalized
        $date1 = new \DateTime('2026-05-08 10:30:00');
        $date2 = new \DateTime('2026-05-08 15:45:00');

        $this->repository->incrementViewCount($rankingId, $date1);
        $this->repository->incrementViewCount($rankingId, $date2);

        // Should be counted as 2 views on the same day
        $count = $this->repository->getViewCount($rankingId, new \DateTime('2026-05-08'));
        $this->assertEquals(2, $count);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->repository = null;
    }
}
