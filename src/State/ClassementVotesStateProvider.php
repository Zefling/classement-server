<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Enum\CodeError;
use App\Entity\Classement;
use App\Entity\ClassementVote;
use App\Repository\ClassementRepository;
use App\Repository\ClassementVoteRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

use App\State\AbstractStateProvider;

class ClassementVotesStateProvider extends AbstractStateProvider implements ProviderInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private Security $security,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
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

        /** @var ClassementVoteRepository $voteRepo */
        $voteRepo = $this->doctrine->getRepository(ClassementVote::class);
        $voteCounts = $voteRepo->getVoteCounts($classement);

        $userVotes = [];
        $user = $this->security->getUser();
        if ($user) {
            $userVotes = $voteRepo->getUserVotes($user, $classement);
        }

        return $this->OK([
            'votes' => $voteCounts,
            'userVotes' => $userVotes,
        ]);
    }
}
