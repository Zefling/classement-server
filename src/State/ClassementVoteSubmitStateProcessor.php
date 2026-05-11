<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Enum\CodeError;
use App\Entity\Classement;
use App\Entity\ClassementVote;
use App\Repository\ClassementRepository;
use App\Repository\ClassementVoteRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use App\State\AbstractStateProvider;

class ClassementVoteSubmitStateProcessor extends AbstractStateProvider implements ProcessorInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            return $this->error(
                CodeError::USER_NOT_AUTHENTICATED,
                'User not authenticated',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $id = $uriVariables['id'] ?? null;

        /** @var ClassementRepository $classementRepo */
        $classementRepo = $this->doctrine->getRepository(Classement::class);
        $classement = $classementRepo->findByIdOrlinkName($id);

        if (!$classement) {
            return $this->error(
                CodeError::CLASSEMENT_NOT_FOUND,
                'Classement not found',
                Response::HTTP_NOT_FOUND
            );
        }

        $voteTypes = $data->vote;

        if ($voteTypes !== null && !is_array($voteTypes)) {
            $voteTypes = [$voteTypes];
        }

        if ($voteTypes !== null) {
            foreach ($voteTypes as $voteType) {
                if (!is_string($voteType) || empty($voteType)) {
                    return $this->error(
                        CodeError::INVALID_PARAMETER,
                        'Each vote type must be a non-empty string emoji',
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
            $voteTypes = array_unique($voteTypes);
        }

        /** @var ClassementVoteRepository $voteRepo */
        $voteRepo = $this->doctrine->getRepository(ClassementVote::class);
        $existingVotes = $voteRepo->findByUserAndClassement($user, $classement);

        if ($voteTypes === null || empty($voteTypes)) {
            if (!empty($existingVotes)) {
                $voteRepo->removeAllByUserAndClassement($user, $classement);
            }
            $voteCounts = $voteRepo->getVoteCounts($classement);

            return $this->OK([
                'action'    => 'removed',
                'votes'     => $voteCounts,
                'votesUser' => [],
            ]);
        }

        $existingVoteTypes = array_map(fn($vote) => $vote->getVoteType(), $existingVotes);
        $votesToAdd = array_diff($voteTypes, $existingVoteTypes);
        $votesToRemove = array_diff($existingVoteTypes, $voteTypes);

        $entityManager = $this->doctrine->getManager();

        foreach ($existingVotes as $vote) {
            if (in_array($vote->getVoteType(), $votesToRemove)) {
                $entityManager->remove($vote);
            }
        }

        foreach ($votesToAdd as $voteType) {
            $vote = new ClassementVote();
            $vote->setUser($user);
            $vote->setClassement($classement);
            $vote->setVoteType($voteType);
            $entityManager->persist($vote);
        }

        $entityManager->flush();

        $voteCounts = $voteRepo->getVoteCounts($classement);
        $action = !empty($votesToAdd) && !empty($votesToRemove)
            ? 'updated'
            : (!empty($votesToAdd) ? 'created' : 'updated');

        return $this->OK([
            'action'    => $action,
            'votes'     => $voteCounts,
            'votesUser' => $voteTypes,
        ]);
    }
}
