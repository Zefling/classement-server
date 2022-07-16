<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiAdminClassementStatusController extends AbstractApiController implements TokenAuthenticatedController
{

    #[Route(
        '/api/admin/classement/status/{id}',
        name: 'app_api_admin_classement_status',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_item_operations_name' => 'app_api_admin_classement_status',
        ],
    )]
    public function __invoke(#[CurrentUser] ?User $user, string $id, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!($user?->isModerator())) {
            return $this->error(CodeError::USER_NO_PERMISSION, 'moderation role required', Response::HTTP_UNAUTHORIZED);
        }

        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classement = $rep->findOneBy(['rankingId' => $id]);

        if ($classement !== null) {

            $params = $request->toArray();

            $status = $params['status'];
            $type = $params['type'];

            try {
                if ($type === 'delete' && $type !== null) {
                    $classement->setDeleted($status);
                } else if ($type === 'hide' && $type !== null) {
                    $classement->setHidden($status);
                } else {
                    return $this->error(CodeError::STATUS_ERROR, 'Status in error', Response::HTTP_BAD_REQUEST);
                }

                //save db data
                $entityManager = $doctrine->getManager();
                $entityManager->persist($classement);
                $entityManager->flush();

                return $this->OK();
            } catch (Error $e) {
                return $this->error(CodeError::REQUEST_ERROR, 'Classement not found', Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
