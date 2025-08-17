<?php

namespace App\Repository;

use App\Entity\Classement;
use App\Entity\ClassementHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClassementHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClassementHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClassementHistory[]    findAll()
 * @method ClassementHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassementHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClassementHistory::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ClassementHistory $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(ClassementHistory $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByHistory(string $id)
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('count(c.id) as count')
            ->from(Classement::class, 'c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0')
            ->andWhere('c.rankingId = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (isset($result[0]['count']) && $result[0]['count'] > 0) {
            return $this->getEntityManager()->createQueryBuilder()
                ->select('c.id, c.date, c.name')
                ->from(ClassementHistory::class, 'c')
                ->where('c.rankingId = :id')
                ->andWhere('c.deleted = 0')
                ->setParameter('id', $id)
                ->getQuery()
                ->getResult();
        }
        return null;
    }

    /**
     * count elements by ranking from a list of ranking ids
     */
    public function countByRankingId(array $listRankingIds)
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('count(c.rankingId)', 'c.rankingId')
            ->from(ClassementHistory::class, 'c')
            ->where('c.rankingId IN (:ids)')
            ->setParameter('ids', $listRankingIds)
            ->groupBy('c.rankingId')
            ->getQuery()
            ->getResult();

        $list = [];
        if (!empty($result) && is_array(($result))) {
            foreach ($result as $line) {
                $list[$line['rankingId']] = $line['1'];
            }
        }
        return $list;
    }
}
