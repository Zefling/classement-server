<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Classement;
use App\Entity\User;
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
                    $qb->expr()->eq('c.id', ':id'),
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
        string $name = null,
        string $category = null,
        int $page = 1,
        int $pageSize = 25
    ) {
        $req =  $this->createQueryBuilder('c')
            ->where('c.parent = 1')
            ->andWhere('c.deleted = 0')
            ->andWhere('c.hidden = 0');

        if (!empty($category)) {
            $req = $req->andWhere('c.category = :category')->setParameter('category', "{$category}");
        }
        if (!empty($name)) {
            $req = $req->andWhere('c.name LIKE :name')->setParameter('name', "%{$name}%");
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
        string $name = null,
        string $category = null
    ): int {
        $req =  $this->_em->createQueryBuilder()
            ->select('count(c.templateId) as COUNT')
            ->from(Classement::class, 'c')
            ->where('c.parent = 1')
            ->andWhere('c.deleted = 0')
            ->andWhere('c.hidden = 0');

        if (!empty($category)) {
            $req = $req->andWhere('c.category = :category')->setParameter('category', "{$category}");
        }
        if (!empty($name)) {
            $req = $req->andWhere('c.name LIKE :name')->setParameter('name', "%{$name}%");
        }

        return $req
            ->getQuery()
            ->getOneOrNullResult()['COUNT'];
    }


    /**
     * count elements by template from a list of template ids
     */
    public function countByTemplateId(array $listTemplateIds)
    {
        $result = $this->_em->createQueryBuilder()
            ->select('count(c.templateId)', 'c.templateId')
            ->from(Classement::class, 'c')
            ->where('c.templateId IN (:ids)')
            ->andWhere('c.deleted = 0')
            ->andWhere('c.hidden = 0')
            ->setParameter('ids', $listTemplateIds)
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
    public function countByCategories()
    {
        $result = $this->_em->createQueryBuilder()
            ->select('count(c.category)', 'c.category')
            ->from(Classement::class, 'c')
            ->where('c.parent = 1')
            ->andWhere('c.deleted = 0')
            ->andWhere('c.hidden = 0')
            ->groupBy('c.category')
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
    public function findByTemplateCategory()
    {
        // mort recent IDs by categories
        $result = $this->_em->createQueryBuilder()
            ->select('MAX(c1.id) as id')
            ->from(Classement::class, 'c1')
            ->where('c1.parent = 1')
            ->andWhere('c1.deleted = 0')
            ->andWhere('c1.hidden = 0')
            ->groupBy('c1.category')
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
    public function findByTemplate(string $id)
    {
        return $this->createQueryBuilder('c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0')
            ->andWhere('c.templateId = :id')
            ->setParameter('id', $id)
            ->orderBy('c.dateCreate')
            ->getQuery()
            ->getResult();
    }

    /**
     * find classement by templateId ans by userId
     */
    public function findByTemplateAndUser(string $id, User $user)
    {
        return $this->createQueryBuilder('c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0')
            ->andWhere('c.templateId = :id')
            ->andWhere('c.User = :user')
            ->setParameter('id', $id)
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
     *  last 
     */
    public function findLast(int $limit)
    {
        // more recent template ()
        $result = $this->_em->createQueryBuilder()
            ->select('c.templateId')
            ->from(Classement::class, 'c')
            ->where('c.deleted = 0')
            ->andWhere('c.hidden = 0')
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
            $resultIds = $this->_em->createQueryBuilder()
                ->select('MAX(c.id) as id')
                ->from(Classement::class, 'c')
                ->where('c.deleted = 0')
                ->andWhere('c.hidden = 0')
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
}
