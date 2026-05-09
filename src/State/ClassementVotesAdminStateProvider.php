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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use App\State\AbstractStateProvider;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class ClassementVotesAdminStateProvider extends AbstractStateProvider implements ProviderInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private Security $security,
        private RequestStack $requestStack,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if (!$user || !$user->isModerator()) {
            return $this->error(
                CodeError::USER_NO_PERMISSION,
                'Admin access required',
                HttpFoundationResponse::HTTP_FORBIDDEN
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

        $request = $this->requestStack->getCurrentRequest();
        $includeDetails = $request?->query->get('details', 'false') === 'true';
        $includeByUser = $request?->query->get('byUser', 'false') === 'true';

        /** @var ClassementVoteRepository $voteRepo */
        $voteRepo = $this->doctrine->getRepository(ClassementVote::class);

        $voteCounts = $voteRepo->getVoteCounts($classement);

        $response = [
            'votes' => $voteCounts,
        ];

        if ($includeDetails || $includeByUser) {
            $votes = $voteRepo->findBy(['classement' => $classement], ['dateCreate' => 'DESC']);

            $response['totalVotes'] = count($votes);

            if ($includeDetails) {
                $voteDetails = [];
                foreach ($votes as $vote) {
                    $voteDetails[] = [
                        'id' => $vote->getId(),
                        'voteType' => $vote->getVoteType(),
                        'userId' => $vote->getUser()->getId(),
                        'username' => $vote->getUser()->getUsername(),
                        'dateCreate' => $vote->getDateCreate()->format('Y-m-d H:i:s'),
                    ];
                }
                $response['voteDetails'] = $voteDetails;
            }

            if ($includeByUser) {
                $votesByUser = [];
                foreach ($votes as $vote) {
                    $userId = $vote->getUser()->getId();
                    if (!isset($votesByUser[$userId])) {
                        $votesByUser[$userId] = [
                            'userId' => $userId,
                            'username' => $vote->getUser()->getUsername(),
                            'votes' => [],
                        ];
                    }
                    $votesByUser[$userId]['votes'][] = $vote->getVoteType();
                }
                $response['totalUsers'] = count($votesByUser);
                $response['votesByUser'] = array_values($votesByUser);
            }
        }

        return $this->OK($response);
    }
}
