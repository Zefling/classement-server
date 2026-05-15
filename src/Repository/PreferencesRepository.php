<?php

namespace App\Repository;

use App\Entity\Preferences;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Preferences|null find($id, $lockMode = null, $lockVersion = null)
 * @method Preferences|null findOneBy(array $criteria, array $orderBy = null)
 * @method Preferences[]    findAll()
 * @method Preferences[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreferencesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Preferences::class);
    }

    /**
     * Find preferences by user
     */
    public function findByUser(User $user): ?Preferences
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * Save preferences entity
     */
    public function save(Preferences $preferences, bool $flush = true): void
    {
        $this->getEntityManager()->persist($preferences);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
