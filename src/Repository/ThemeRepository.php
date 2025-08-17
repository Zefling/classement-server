<?php

namespace App\Repository;

use App\Entity\Theme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Theme>
 *
 * @method Theme|null find($id, $lockMode = null, $lockVersion = null)
 * @method Theme|null findOneBy(array $criteria, array $orderBy = null)
 * @method Theme[]    findAll()
 * @method Theme[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Theme::class);
    }

    public function save(Theme $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Theme $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * find list theme by criterion
     * 
     */
    public function findBySearchField(
        ?int $user = null,
        ?string $name = null,
        ?string $mode = null,
        int $page = 1,
        int $pageSize = 25
    ) {
        $req =  $this->createQueryBuilder('c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0');

        if (!empty($mode)) {
            if ($mode === 'default' || $mode === 'teams' || $mode === 'columns') {
                $req = $req->andWhere('c.mode IN (:mode)')->setParameter('mode', ['default', 'teams', 'columns']);
            } else {
                $req = $req->andWhere('c.mode = :mode')->setParameter('mode', "{$mode}");
            }
        }
        if (!empty($name)) {
            $req = $req->andWhere('c.name LIKE :name')->setParameter('name', "%{$name}%");
        }
        if ($user !== null) {
            $req = $req->andWhere('c.User != :user')->setParameter('user', $user);
        }

        return $req
            ->orderBy('c.dateCreate', 'DESC')
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();
    }

    /**
     * count theme by criterion
     * 
     */
    public function countBySearchField(
        ?int $user = null,
        ?string $name = null,
        ?string $mode = null
    ): int {
        $req =  $this->getEntityManager()->createQueryBuilder()
            ->select('count(c.themeId) as COUNT')
            ->from(Theme::class, 'c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0');

        if (!empty($mode)) {
            $req = $req->andWhere('c.mode = :mode')->setParameter('mode', "{$mode}");
        }
        if (!empty($name)) {
            $req = $req->andWhere('c.name LIKE :name')->setParameter('name', "%{$name}%");
        }
        if ($user !== null) {
            $req = $req->andWhere('c.User != :user')->setParameter('user', $user);
        }

        return $req
            ->getQuery()
            ->getOneOrNullResult()['COUNT'];
    }
}
