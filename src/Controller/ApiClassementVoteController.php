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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiClassementVoteController extends AbstractApiController implements TokenAuthenticatedController
{
    public static function getName(): string
    {
        return 'app_api_classement_vote';
    }

    public function __invoke(
        string $id,
        Request $request,
        ManagerRegistry $doctrine,
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->error(
                CodeError::USER_NOT_AUTHENTICATED,
                'User not authenticated',
                Response::HTTP_UNAUTHORIZED
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

        // Get vote types from request
        $data = json_decode($request->getContent(), true);
        $voteTypes = $data['voteType'] ?? null;

        // Normalize to array
        if ($voteTypes !== null && !is_array($voteTypes)) {
            $voteTypes = [$voteTypes];
        }

        // Validate vote types
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
            // Remove duplicates
            $voteTypes = array_unique($voteTypes);
        }

        /** @var ClassementVoteRepository $voteRepo */
        $voteRepo = $doctrine->getRepository(ClassementVote::class);
        $existingVotes = $voteRepo->findByUserAndClassement($user, $classement);

        // If voteTypes is null or empty, remove all votes
        if ($voteTypes === null || empty($voteTypes)) {
            if (!empty($existingVotes)) {
                $voteRepo->removeAllByUserAndClassement($user, $classement);
            }
            $voteCounts = $voteRepo->getVoteCounts($classement);
            return $this->OK([
                'action' => 'removed',
                'votes' => $voteCounts,
                'userVotes' => [],
            ]);
        }

        // Get existing vote types
        $existingVoteTypes = array_map(fn($vote) => $vote->getVoteType(), $existingVotes);

        // Determine which votes to add and which to remove
        $votesToAdd = array_diff($voteTypes, $existingVoteTypes);
        $votesToRemove = array_diff($existingVoteTypes, $voteTypes);

        $entityManager = $doctrine->getManager();

        // Remove votes that are no longer selected
        foreach ($existingVotes as $vote) {
            if (in_array($vote->getVoteType(), $votesToRemove)) {
                $entityManager->remove($vote);
            }
        }

        // Add new votes
        foreach ($votesToAdd as $voteType) {
            $vote = new ClassementVote();
            $vote->setUser($user);
            $vote->setClassement($classement);
            $vote->setVoteType($voteType);
            $entityManager->persist($vote);
        }

        $entityManager->flush();

        $voteCounts = $voteRepo->getVoteCounts($classement);
        $action = !empty($votesToAdd) && !empty($votesToRemove) ? 'updated' : 
                  (!empty($votesToAdd) ? 'created' : 'updated');

        return $this->OK([
            'action' => $action,
            'votes' => $voteCounts,
            'userVotes' => $voteTypes,
        ]);
    }
}
