<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiDeleteClassementController extends AbstractApiController implements TokenAuthenticatedController
{

    #[Route(
        '/api/classement/{id}',
        name: 'app_api_classement_delete',
        methods: ['DELETE'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_item_operations_name' => 'delete_publication',
        ],
    )]
    public function __invoke(#[CurrentUser] ?User $user, string $id, ManagerRegistry $doctrine): Response
    {
        if (null === $user) {
            return $this->error(CodeError::USER_NOT_FOUND, 'missing credentials', Response::HTTP_UNAUTHORIZED);
        }

        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classement = $rep->findOneBy(['rankingId' => $id, 'deleted' => 0]);

        if ($classement !== null) {

            // if the UserId as same with the current user or if moderator
            if ($classement->getUser()->getId() === $user->getId() || $user->isModerator()) {
                $user->isUser();

                // mapping
                $classement->setDeleted(true);

                //save db data
                $entityManager = $doctrine->getManager();
                $entityManager->persist($classement);
                $entityManager->flush();

                // return updated data
                return $this->OK();
            } else {
                return $this->error(
                    CodeError::USER_NO_PERMISSION,
                    'You don\'t have allow for that.',
                    Response::HTTP_NOT_FOUND
                );
            }
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
