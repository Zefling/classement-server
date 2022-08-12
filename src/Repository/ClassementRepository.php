<?php

namespace App\Repository;

use App\Entity\Classement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
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
     * find list without parent
     * 
     */
    public function findByNameTemplateField(
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
            $req = $req->andWhere('c.category = :category')->setParameter('category', "${category}");
        }
        if (!empty($name)) {
            $req = $req->andWhere('c.name LIKE :name')->setParameter('name', "%${name}%");
        }

        return $req
            ->orderBy('c.dateCreate', 'DESC')
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize + 1)
            ->getQuery()
            ->getResult();
    }


    /**
     * find templates group (last first)
     * 
    //  */
    // public function findByRankingIds(array $ids)
    // {
    //     return $this->createQueryBuilder('c1')
    //         ->where('c1.parent = 1')
    //         ->andWhere('c1.deleted = 0')
    //         ->andWhere('c1.hidden = 0')
    //         ->orderBy('c1.dateCreate', 'DESC')
    //         ->groupBy('c1.category')
    //         ->getQuery()
    //         ->getResult();
    // }


    /**
     * find templates group (last first)
     * 
     */
    public function findByTemplateCategory()
    {
        // mort recent IDs by vategories
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

            // get by mort recent IDs
            return $this->createQueryBuilder('c1')
                ->where('c1.id IN (:ids)')
                ->setParameter('ids', $list)
                ->getQuery()
                ->getResult();
        } else {
            return null;
        }
    }

    /**
     * find classement by templateId
     * 
     */
    public function findByTemplate(string $id)
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent = 1')
            ->andWhere('c.deleted = 0')
            ->andWhere('c.hidden = 0')
            ->andWhere('c.templateId = :id')
            ->setParameter('id', $id)
            ->orderBy('c.dateCreate')
            ->getQuery()
            ->getResult();
    }


    /**
     * find first classement by templateId
     * 
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
     * 
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
     * counts classements by users
     * 
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


    // /**
    //  * @return Classement[] Returns an array of Classement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Classement
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
