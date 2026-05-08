<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Classement;
use App\Entity\ClassementVote;
use App\Entity\User;
use App\Repository\ClassementRepository;
use App\Repository\ClassementVoteRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiGetClassementVotesController extends AbstractApiController
{
    public static function getName(): string
    {
        return 'app_api_classement_votes_get';
    }

    public function __invoke(
        string $id,
        #[CurrentUser()] ?User $user,
        ManagerRegistry $doctrine,
    ): Response {
        error_log("ApiGetClassementVotesController called with id: " . $id);
        
        // Get classement
        /** @var ClassementRepository $classementRepo */
        $classementRepo = $doctrine->getRepository(Classement::class);
        $classement = $classementRepo->findByIdOrlinkName($id);

        error_log("Classement found: " . ($classement ? "yes" : "no"));

        if (!$classement) {
            return $this->error(
                CodeError::CLASSEMENT_NOT_FOUND,
                'Classement not found',
                Response::HTTP_NOT_FOUND
            );
        }

        /** @var ClassementVoteRepository $voteRepo */
        $voteRepo = $doctrine->getRepository(ClassementVote::class);
        $voteCounts = $voteRepo->getVoteCounts($classement);

        error_log("Vote counts: " . json_encode($voteCounts));

        $userVotes = [];

        if ($user) {
            $userVotes = $voteRepo->getUserVotes($user, $classement);
            error_log("User votes: " . json_encode($userVotes));
        }

        return $this->OK([
            'votes' => $voteCounts,
            'userVotes' => $userVotes,
        ]);
    }
}
