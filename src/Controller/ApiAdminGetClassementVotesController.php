<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Classement;
use App\Entity\ClassementVote;
use App\Repository\ClassementRepository;
use App\Repository\ClassementVoteRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiAdminGetClassementVotesController extends AbstractApiController implements TokenAuthenticatedController
{
    public static function getName(): string
    {
        return 'app_api_admin_classement_votes_get';
    }

    public function __invoke(
        string $id,
        ManagerRegistry $doctrine,
        \Symfony\Component\HttpFoundation\Request $request,
    ): Response {
        $user = $this->getUser();

        if (!$user || !$user->isModerator()) {
            return $this->error(
                CodeError::USER_NO_PERMISSION,
                'Admin access required',
                Response::HTTP_FORBIDDEN
            );
        }

        // Get classement
        /** @var ClassementRepository $classementRepo */
        $classementRepo = $doctrine->getRepository(Classement::class);
        $classement = $classementRepo->findByIdOrlinkName($id);

        if (!$classement) {
            return $this->error(
                CodeError::CLASSEMENT_NOT_FOUND,
                'Classement not found',
                Response::HTTP_NOT_FOUND
            );
        }

        // Get query parameters
        $includeDetails = $request->query->get('details', 'false') === 'true';
        $includeByUser = $request->query->get('byUser', 'false') === 'true';

        /** @var ClassementVoteRepository $voteRepo */
        $voteRepo = $doctrine->getRepository(ClassementVote::class);
        
        // Get vote counts (always included)
        $voteCounts = $voteRepo->getVoteCounts($classement);
        
        $response = [
            'votes' => $voteCounts,
        ];

        // Only fetch votes if details or byUser is requested
        if ($includeDetails || $includeByUser) {
            $votes = $voteRepo->findBy(['classement' => $classement], ['dateCreate' => 'DESC']);
            
            $response['totalVotes'] = count($votes);
            
            // Add vote details if requested
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

            // Add votes by user if requested
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
