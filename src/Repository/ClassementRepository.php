<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Classement;
use App\Entity\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Classement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Classement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Classement[]    findAll()
 * @method Classement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classement::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Classement $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Classement $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * find list template by criterion
     * 
     */
    public function findByIdOrlinkName(string  $id)
    {
        $qb = $this->_em->createQueryBuilder();

        return  $this->createQueryBuilder('c')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('c.rankingId', ':id'),
                    $qb->expr()->eq('c.linkId', ':id')
                )
            )
            ->andWhere('c.deleted = 0')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * find list template by criterion
     * 
     */
    public function findBySearchTemplateField(
        ?string $name = null,
        ?string $mode = null,
        ?string $category = null,
        ?string $tag = null,
        bool $adult = false,
        bool $all = false,
        int $page = 1,
        int $pageSize = 25
    ) {
        $req =  $this->createQueryBuilder('c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0');

        if (!$all) {
            $req = $req->andWhere('c.parent = 1');
        }
        if (!$adult) {
            $req = $req->andWhere('c.adult = 0');
        }
        if (!empty($category)) {
            $req = $req->andWhere('c.category = :category')->setParameter('category', "{$category}");
        }
        if (!empty($mode)) {
            $req = $req->andWhere('c.mode = :mode')->setParameter('mode', "{$mode}");
        }
        if (!empty($name)) {
            $req = $req->andWhere('c.name LIKE :name')->setParameter('name', "%{$name}%");
        }
        if (!empty($tag)) {
            $req = $req->join('c.tags', 't')
                ->andWhere("t.label LIKE :label")
                ->setParameter('label', "%$tag%");
        }

        return $req
            ->orderBy('c.dateCreate', 'DESC')
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();
    }

    /**
     * count template by criterion
     * 
     */
    public function countBySearchTemplateField(
        ?string $name = null,
        ?string $mode = null,
        ?string $category = null,
        ?string $tag = null,
        bool $all = false,
        bool $adult = false
    ): int {
        $req =  $this->_em->createQueryBuilder()
            ->select('count(c.templateId) as COUNT')
            ->from(Classement::class, 'c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0');

        if (!$all) {
            $req = $req->andWhere('c.parent = 1');
        }
        if (!$adult) {
            $req = $req->andWhere('c.adult = 0');
        }
        if (!empty($category)) {
            $req = $req->andWhere('c.category = :category')->setParameter('category', "{$category}");
        }
        if (!empty($mode)) {
            $req = $req->andWhere('c.mode = :mode')->setParameter('mode', "{$mode}");
        }
        if (!empty($name)) {
            $req = $req->andWhere('c.name LIKE :name')->setParameter('name', "%{$name}%");
        }
        if (!empty($tag)) {
            $req = $req->join('c.tags', 't')
                ->andWhere("t.label LIKE :label")
                ->setParameter('label', "%$tag%");
        }

        return $req
            ->getQuery()
            ->getOneOrNullResult()['COUNT'];
    }

    /**
     * count elements by template from a list of template ids
     */
    public function countByTemplateId(array $listTemplateIds, bool $adult = false)
    {
        $req = $this->_em->createQueryBuilder()
            ->select('count(c.templateId)', 'c.templateId')
            ->from(Classement::class, 'c')
            ->where('c.templateId IN (:ids)')
            ->andWhere('c.deleted = 0')
            ->andWhere('c.hidden = 0');

        if (!$adult) {
            $req = $req->andWhere('c.adult = 0');
        }

        $result = $req->setParameter('ids', $listTemplateIds)
            ->groupBy('c.templateId')
            ->getQuery()
            ->getResult();

        $list = [];
        if (!empty($result) && is_array(($result))) {
            foreach ($result as $line) {
                $list[$line['templateId']] = $line['1'];
            }
        }
        return $list;
    }

    /**
     * count elements by categories
     */
    public function countByCategories(bool $adult = false)
    {
        $req = $this->_em->createQueryBuilder()
            ->select('count(c.category)', 'c.category')
            ->from(Classement::class, 'c')
            ->where('c.parent = 1')
            ->andWhere('c.deleted = 0')
            ->andWhere('c.hidden = 0');

        if (!$adult) {
            $req = $req->andWhere('c.adult = 0');
        }

        $result = $req->groupBy('c.category')
            ->getQuery()
            ->getResult();

        $list = [];
        // print_r($result);
        if (!empty($result) && is_array(($result))) {
            foreach ($result as $line) {
                $list[$line['category']->value] = $line['1'];
            }
        }
        return $list;
    }

    /**
     * find templates group (last first)
     */
    public function findByTemplateCategory(bool $adult = false)
    {
        // mort recent IDs by categories
        $req = $this->_em->createQueryBuilder()
            ->select('MAX(c1.id) as id')
            ->from(Classement::class, 'c1')
            ->where('c1.parent = 1')
            ->andWhere('c1.deleted = 0')
            ->andWhere('c1.hidden = 0');

        if (!$adult) {
            $req = $req->andWhere('c1.adult = 0');
        }

        $result = $req->groupBy('c1.category')
            ->getQuery()
            ->getResult();

        if (is_array($result) && !empty($result)) {
            $list = [];
            foreach ($result as $e) {
                $list[] = $e['id'];
            }

            // get by more recent IDs
            return $this->createQueryBuilder('c1')
                ->where('c1.id IN (:ids)')
                ->setParameter('ids', $list)
                ->orderBy('c1.dateCreate', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            return null;
        }
    }

    /**
     * find classement by templateId
     */
    public function findByTemplate(string $id,  bool $adult = false)
    {
        $req = $this->createQueryBuilder('c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0')
            ->andWhere('c.templateId = :id');

        if (!$adult) {
            $req = $req->andWhere('c.adult = 0');
        }

        return $req->setParameter('id', $id)
            ->orderBy('c.dateCreate')
            ->getQuery()
            ->getResult();
    }

    /**
     * find classement by templateId ans by userId
     */
    public function findByTemplateAndUser(string $id, User $user, bool  $adult = false)
    {
        $req = $this->createQueryBuilder('c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0')
            ->andWhere('c.templateId = :id')
            ->andWhere('c.User = :user');

        if (!$adult) {
            $req = $req->andWhere('c.adult = 0');
        }

        return $req->setParameter('id', $id)
            ->setParameter('user', $user)
            ->orderBy('c.dateCreate')
            ->getQuery()
            ->getResult();
    }

    /**
     * update category by templateId
     */
    public function updateCatagoryByTemplateId(string $id, Category $category)
    {
        return $this->_em->createQueryBuilder()
            ->update(Classement::class, 'c')
            ->set('c.category', ':category')
            ->where('c.templateId = :templateId')
            ->setParameter('category', $category)
            ->setParameter('templateId', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * find first classement by templateId
     */
    public function findByTemplateParent(string $id)
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent = 1')
            ->andwhere('c.deleted = 0')
            ->andWhere('c.hidden = 0')
            ->andWhere('c.templateId = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

    /**
     * find first classement by templateId
     */
    public function findByTemplateFirst(string $id)
    {
        return $this->createQueryBuilder('c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0')
            ->andWhere('c.templateId = :id')
            ->setParameter('id', $id)
            ->orderBy('c.dateCreate')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

    /**
     * counts classements by users (all for admin)
     */
    public function findByUserIds(array $ids)
    {
        return $this->createQueryBuilder('c')
            ->where('c.User IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('c.dateCreate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 
     */
    public function findAllLast(int $limit,  bool $adult = false)
    {
        // more recent template ()
        $req =  $this->createQueryBuilder('c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0');

        if (!$adult) {
            $req = $req->andWhere('c.adult = 0');
        }

        return $req
            ->orderBy('CASE WHEN c.dateChange IS NOT NULL THEN c.dateChange ELSE c.dateCreate END', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }


    /**
     *  last 
     */
    public function findLastTemplate(int $limit,  bool $adult = false)
    {
        // more recent template ()
        $req = $this->_em->createQueryBuilder()
            ->select('c.templateId')
            ->from(Classement::class, 'c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0');

        if (!$adult) {
            $req = $req->andWhere('c.adult = 0');
        }

        $result = $req
            ->orderBy('MAX(c.dateCreate)', 'DESC')
            ->groupBy('c.templateId')
            ->setMaxResults($limit)

            ->getQuery()
            ->getResult();

        if (is_array($result) && !empty($result)) {

            $listTemplate = [];
            foreach ($result as $e) {
                $listTemplate[] = $e['templateId'];
            }

            // more recent IDs by template ()
            $reqIds = $this->_em->createQueryBuilder()
                ->select('MAX(c.id) as id')
                ->from(Classement::class, 'c')
                ->where('c.deleted = 0')
                ->andWhere('c.hidden = 0');

            if (!$adult) {
                $reqIds = $reqIds->andWhere('c.adult = 0');
            }

            $resultIds = $reqIds
                ->andWhere('c.templateId IN (:ids)')
                ->groupBy('c.templateId')
                ->setParameter('ids', $listTemplate)
                ->getQuery()
                ->getResult();

            $list = [];
            foreach ($resultIds as $e) {
                $list[] = $e['id'];
            }

            // get by more recent by IDs
            return $this->createQueryBuilder('c')
                ->where('c.id IN (:ids)')
                ->setParameter('ids', $list)
                ->orderBy('c.dateCreate', 'DESC')
                // ->orderBy('CASE WHEN (c.dateChange IS NOT NULL) THEN c.dateChange ELSE c.dateCreate END', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            return null;
        }
    }

    /**
     * Return count classements by filter
     */
    public function countByKey(array $params): int
    {

        $q = $this->_em->createQueryBuilder()
            ->select('count(c.name) AS count')
            ->from(Classement::class, 'c');

        $count = 0;
        foreach ($params as $key => $value) {
            $action = $value[0] === '%' ? 'LIKE' : '=';
            if ($count === 0) {
                $q = $q->where("c.$key $action :$key")
                    ->setParameter($key, $value);
            } else {
                $q = $q->andWhere("c.$key $action :$key")
                    ->setParameter($key, $value);
            }
            $count++;
        }

        return $q->getQuery()
            ->getResult()[0]["count"];
    }


    /**
     * Return classements by filter
     */
    public function findByKey(array $params, string $sort, string $direction, int $page, int $pageSize): array
    {

        $q = $this->createQueryBuilder('c');
        $count = 0;
        foreach ($params as $key => $value) {
            $action = $value[0] === '%' ? 'LIKE' : '=';
            if ($count === 0) {
                $q = $q->where("c.$key $action :$key")
                    ->setParameter($key, $value);
            } else {
                $q = $q->andWhere("c.$key $action :$key")
                    ->setParameter($key, $value);
            }
            $count++;
        }

        return $q->setFirstResult(($page - 1) * $pageSize)
            ->orderBy("c.$sort", $direction)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();
    }

    /**
     * Stats by day
     */
    public function getStatsByDay(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                DATE(c.date_Create) as date,
                COUNT(c.id) as count,
                SUM(CASE WHEN c.deleted = 1 THEN 1 ELSE 0 END) as deleted,
                SUM(CASE WHEN c.hidden = 1 THEN 1 ELSE 0 END) as hide,
                SUM(CASE WHEN c.parent = 1 THEN 1 ELSE 0 END) as parent
            FROM classement c
            WHERE c.date_create BETWEEN :startDate AND :endDate
            GROUP BY date
            ORDER BY date ASC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate'   => $endDate->format('Y-m-d H:i:s')
        ]);

        return $result->fetchAllAssociative();
    }

    /**
     * Stats by week
     */
    public function getStatsByWeek(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                YEAR(c.date_create) as year,
                WEEK(c.date_create, 1) as week,
                COUNT(c.id) as count,
                SUM(CASE WHEN c.deleted = 1 THEN 1 ELSE 0 END) as deleted,
                SUM(CASE WHEN c.hidden = 1 THEN 1 ELSE 0 END) as hide,
                SUM(CASE WHEN c.parent = 1 THEN 1 ELSE 0 END) as parent
            FROM classement c
            WHERE c.date_create BETWEEN :startDate AND :endDate
            GROUP BY year, week
            ORDER BY year ASC, week ASC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate'   => $endDate->format('Y-m-d H:i:s')
        ]);

        return $result->fetchAllAssociative();
    }

    /**
     * Stats by month
     */
    public function getStatsByMonth(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                YEAR(c.date_create) as year, 
                MONTH(c.date_create) as month,
                COUNT(c.id) as count,
                SUM(CASE WHEN c.deleted = 1 THEN 1 ELSE 0 END) as deleted,
                SUM(CASE WHEN c.hidden = 1 THEN 1 ELSE 0 END) as hide,
                SUM(CASE WHEN c.parent = 1 THEN 1 ELSE 0 END) as parent
            FROM classement c
            WHERE c.date_create BETWEEN :startDate AND :endDate
            GROUP BY year, month
            ORDER BY year ASC, month ASC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate'   => $endDate->format('Y-m-d H:i:s')
        ]);

        return $result->fetchAllAssociative();
    }
}
