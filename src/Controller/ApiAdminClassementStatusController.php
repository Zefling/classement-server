<?php

namespace App\Controller;

use App\Controller\Common\ClassementStatusController;
use App\Controller\Common\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Classement;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiAdminClassementStatusController extends ClassementStatusController implements TokenAuthenticatedController
{
    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_admin_classement_status';
    }

    public function __invoke(#[CurrentUser] ?User $user, string $id, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!($user?->isModerator())) {
            return $this->error(CodeError::USER_NO_PERMISSION, 'moderation role required', Response::HTTP_UNAUTHORIZED);
        }

        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classement = $rep->findOneBy(['rankingId' => $id]);

        return $this->update($classement, $request, $rep, $doctrine);
    }
}
