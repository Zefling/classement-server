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
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiClassementStatusController extends ClassementStatusController implements TokenAuthenticatedController
{

    #[Route(
        '/api/classement/status/{id}',
        name: 'app_api_user_classement_status',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_item_operations_name' => 'app_api_user_classement_status',
        ],
    )]
    public function __invoke(#[CurrentUser] ?User $user, string $id, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!($user?->isUser())) {
            return $this->error(CodeError::USER_NO_PERMISSION, 'moderation role required', Response::HTTP_UNAUTHORIZED);
        }

        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classement = $rep->findOneBy(['rankingId' => $id, 'User' => $user]);

        return $this->update($classement, $request, $rep, $doctrine);
    }
}
