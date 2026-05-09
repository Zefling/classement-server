<?php

namespace App\Repository;

use App\Entity\ClassementStatsDaily;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClassementStatsDaily|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClassementStatsDaily|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClassementStatsDaily[]    findAll()
 * @method ClassementStatsDaily[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassementStatsDailyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClassementStatsDaily::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ClassementStatsDaily $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Increment view count for a ranking on a specific date
     */
    public function incrementViewCount(string $rankingId, \DateTimeInterface $date): void
    {
        // Normalize date to midnight
        $normalizedDate = (new \DateTime($date->format('Y-m-d')))->setTime(0, 0, 0);
        
        $stats = $this->findOneBy([
            'rankingId' => $rankingId,
            'date' => $normalizedDate
        ]);

        if ($stats === null) {
            $stats = new ClassementStatsDaily();
            $stats->setRankingId($rankingId);
            $stats->setDate($normalizedDate);
        }

        $stats->incrementViewCount();
        $this->add($stats);
    }

    /**
     * Get view count for a ranking on a specific date
     */
    public function getViewCount(string $rankingId, \DateTimeInterface $date): int
    {
        $normalizedDate = (new \DateTime($date->format('Y-m-d')))->setTime(0, 0, 0);
        
        $stats = $this->findOneBy([
            'rankingId' => $rankingId,
            'date' => $normalizedDate
        ]);
        
        return $stats ? $stats->getViewCount() : 0;
    }

    /**
     * Get view counts for a ranking over a date range
     * Returns an array with dates as keys and counts as values
     */
    public function getViewCountsForPeriod(
        string $rankingId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $results = $this->createQueryBuilder('s')
            ->where('s.rankingId = :rankingId')
            ->andWhere('s.date >= :startDate')
            ->andWhere('s.date <= :endDate')
            ->setParameter('rankingId', $rankingId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $stat) {
            $counts[$stat->getDate()->format('Y-m-d')] = $stat->getViewCount();
        }

        return $counts;
    }

    /**
     * Get top viewed rankings for a specific date
     */
    public function getTopViewedForDate(\DateTimeInterface $date, int $limit = 10): array
    {
        $normalizedDate = (new \DateTime($date->format('Y-m-d')))->setTime(0, 0, 0);
        
        return $this->createQueryBuilder('s')
            ->where('s.date = :date')
            ->setParameter('date', $normalizedDate)
            ->orderBy('s.viewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total views for a ranking across all dates
     */
    public function getTotalViewCount(string $rankingId): int
    {
        $result = $this->createQueryBuilder('s')
            ->select('SUM(s.viewCount) as total')
            ->where('s.rankingId = :rankingId')
            ->setParameter('rankingId', $rankingId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Get view counts for multiple rankings on a specific date
     */
    public function getViewCountsForDate(array $rankingIds, \DateTimeInterface $date): array
    {
        if (empty($rankingIds)) {
            return [];
        }

        $normalizedDate = (new \DateTime($date->format('Y-m-d')))->setTime(0, 0, 0);

        $results = $this->createQueryBuilder('s')
            ->where('s.rankingId IN (:ids)')
            ->andWhere('s.date = :date')
            ->setParameter('ids', $rankingIds)
            ->setParameter('date', $normalizedDate)
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $stat) {
            $counts[$stat->getRankingId()] = $stat->getViewCount();
        }

        return $counts;
    }
}
