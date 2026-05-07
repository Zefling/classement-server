<?php

namespace App\Repository;

use App\Entity\Classement;
use App\Entity\ClassementVote;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClassementVote>
 *
 * @method ClassementVote|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClassementVote|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClassementVote[]    findAll()
 * @method ClassementVote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassementVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClassementVote::class);
    }

    /**
     * Find votes by user and classement
     */
    public function findByUserAndClassement(User $user, Classement $classement): array
    {
        return $this->findBy([
            'user' => $user,
            'classement' => $classement,
        ]);
    }

    /**
     * Find a specific vote by user, classement and vote type
     */
    public function findByUserClassementAndType(User $user, Classement $classement, string $voteType): ?ClassementVote
    {
        return $this->findOneBy([
            'user' => $user,
            'classement' => $classement,
            'voteType' => $voteType,
        ]);
    }

    /**
     * Get vote counts for a classement
     * Returns an array with emoji as keys and their count as values
     */
    public function getVoteCounts(Classement $classement): array
    {
        $qb = $this->createQueryBuilder('v')
            ->select('v.voteType, COUNT(v.id) as count')
            ->where('v.classement = :classement')
            ->setParameter('classement', $classement)
            ->groupBy('v.voteType');

        $results = $qb->getQuery()->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['voteType']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * Get user's votes for a classement
     */
    public function getUserVotes(User $user, Classement $classement): array
    {
        $votes = $this->findByUserAndClassement($user, $classement);
        return array_map(fn($vote) => $vote->getVoteType(), $votes);
    }

    /**
     * Remove all votes by user and classement
     */
    public function removeAllByUserAndClassement(User $user, Classement $classement): void
    {
        $votes = $this->findByUserAndClassement($user, $classement);
        foreach ($votes as $vote) {
            $this->getEntityManager()->remove($vote);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * Save a vote
     */
    public function save(ClassementVote $vote, bool $flush = true): void
    {
        $this->getEntityManager()->persist($vote);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a vote
     */
    public function remove(ClassementVote $vote, bool $flush = true): void
    {
        $this->getEntityManager()->remove($vote);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
