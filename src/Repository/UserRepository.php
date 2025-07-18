<?php

namespace App\Repository;

use App\Entity\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(User $entity, bool $flush = true): void
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
    public function remove(User $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Return an user if username or email match
     */
    public function findByIdentifier(string $identifier): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :val')
            ->orWhere('u.email = :val')
            ->setParameter('val', $identifier)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

    /**
     * Return count users by filter
     */
    public function countByKey(array $params): int
    {

        $q = $this->_em->createQueryBuilder()
            ->select('count(u.username) AS count')
            ->from(User::class, 'u');

        $count = 0;
        foreach ($params as $key => $value) {
            $action = $value[0] === '%' ? 'LIKE' : '=';
            if ($count === 0) {
                $q = $q->where("u.$key $action :$key")
                    ->setParameter($key, $value);
            } else {
                $q = $q->andWhere("u.$key $action :$key")
                    ->setParameter($key, $value);
            }
            $count++;
        }

        return $q->getQuery()
            ->getResult()[0]["count"];
    }

    /**
     * Return users by filter
     */
    public function findByKey(array $params, string $sort, string $direction, int $page, int $pageSize): array
    {
        $q = $this->createQueryBuilder('u');
        $count = 0;
        foreach ($params as $key => $value) {
            $action = $value[0] === '%' ? 'LIKE' : '=';
            if ($count === 0) {
                $q = $q->where("u.$key $action :$key")
                    ->setParameter($key, $value);
            } else {
                $q = $q->andWhere("u.$key $action :$key")
                    ->setParameter($key, $value);
            }
            $count++;
        }

        return $q->setFirstResult(($page - 1) * $pageSize)
            ->orderBy("u.$sort", $direction)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();
    }

    /**
     * Test username or email
     */
    public function findUserOrEmail(string $usernameOrEmail)
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :usernameOrEmail')
            ->orWhere('u.email = :usernameOrEmail')
            ->setParameter('usernameOrEmail', $usernameOrEmail)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Stats by day
     */
    public function getStatsByDay(DateTimeInterface $startDate = null, DateTimeInterface $endDate = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                DATE(u.date_create) as date,
                COUNT(u.id) as count,
                SUM(CASE WHEN u.is_validated = 1 THEN 1 ELSE 0 END) as validated,
                SUM(CASE WHEN u.deleted = 1 THEN 1 ELSE 0 END) as deleted
            FROM user u
            WHERE u.date_create BETWEEN :startDate AND :endDate
            GROUP BY date
            ORDER BY date ASC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate'   => $endDate->format('Y-m-d H:i:s')
        ]);

        return $result->fetchAllAssociative();
    }

    /**
     * Stats by week
     */
    public function getStatsByWeek(DateTimeInterface $startDate = null, DateTimeInterface $endDate = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                YEAR(u.date_create) as year,
                WEEK(u.date_create) as week,
                COUNT(u.id) as count,
                SUM(CASE WHEN u.is_validated = 1 THEN 1 ELSE 0 END) as validated,
                SUM(CASE WHEN u.deleted = 1 THEN 1 ELSE 0 END) as deleted
            FROM user u
            WHERE u.date_create BETWEEN :startDate AND :endDate
            GROUP BY year, week
            ORDER BY year ASC, week ASC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate'   => $endDate->format('Y-m-d H:i:s')
        ]);

        return $result->fetchAllAssociative();
    }

    /**
     * Stats by month
     */
    public function getStatsByMonth(DateTimeInterface $startDate = null, DateTimeInterface $endDate = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                YEAR(u.date_create) as year, 
                MONTH(u.date_create) as month,
                COUNT(u.id) as count,
                SUM(CASE WHEN u.is_validated = 1 THEN 1 ELSE 0 END) as validated,
                SUM(CASE WHEN u.deleted = 1 THEN 1 ELSE 0 END) as deleted
            FROM user u
            WHERE u.date_create BETWEEN :startDate AND :endDate
            GROUP BY year, month
            ORDER BY year ASC, month ASC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate'   => $endDate->format('Y-m-d H:i:s')
        ]);

        return $result->fetchAllAssociative();
    }
}
