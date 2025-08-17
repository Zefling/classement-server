<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\ClassementStatusController;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiClassementStatusController extends ClassementStatusController implements TokenAuthenticatedController
{
    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_user_classement_status';
    }

    public function __invoke(#[CurrentUser] ?User $user, string $id, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!($user?->isUser())) {
            return $this->error(CodeError::USER_NO_PERMISSION, 'moderation role required', Response::HTTP_UNAUTHORIZED);
        }

        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classement = $rep->findOneBy(['rankingId' => $id, 'User' => $user]);

        $params = $request->toArray();
        $type = $params['type'];

        if ($type === 'delete' || $type === 'hide' || ($type === 'category' && $classement?->getParent())) {
            return $this->update($classement, $request, $rep, $doctrine);
        }

        return $this->error(CodeError::STATUS_ERROR, 'Not possible', Response::HTTP_FORBIDDEN);
    }
}
