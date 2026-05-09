<?php

namespace App\Repository;

use App\Entity\ClassementStats;
use App\Entity\ClassementStatsDaily;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClassementStats|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClassementStats|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClassementStats[]    findAll()
 * @method ClassementStats[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassementStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClassementStats::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ClassementStats $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Increment view count for a ranking (both global and daily)
     */
    public function incrementViewCount(string $rankingId): void
    {
        // Increment global stats
        $stats = $this->findOneBy(['rankingId' => $rankingId]);

        if ($stats === null) {
            $stats = new ClassementStats();
            $stats->setRankingId($rankingId);
        }

        $stats->incrementViewCount();
        $this->add($stats);

        // Increment daily stats
        $dailyRepo = $this->getEntityManager()->getRepository(ClassementStatsDaily::class);
        $dailyRepo->incrementViewCount($rankingId, new \DateTime());
    }

    /**
     * Get view count for a ranking
     */
    public function getViewCount(string $rankingId): int
    {
        $stats = $this->findOneBy(['rankingId' => $rankingId]);
        return $stats ? $stats->getViewCount() : 0;
    }

    /**
     * Get view counts for multiple rankings
     */
    public function getViewCounts(array $rankingIds): array
    {
        if (empty($rankingIds)) {
            return [];
        }

        $results = $this->createQueryBuilder('s')
            ->where('s.rankingId IN (:ids)')
            ->setParameter('ids', $rankingIds)
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $stat) {
            $counts[$stat->getRankingId()] = $stat->getViewCount();
        }

        return $counts;
    }

    /**
     * Get top viewed rankings
     */
    public function getTopViewed(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.viewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
