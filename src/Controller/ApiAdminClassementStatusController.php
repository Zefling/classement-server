<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
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

            $status = $params['status'] === true || $params['status'] === 'true' ? true : false;
            $type = $params['type'];

            $change = false;

            if ($type === 'delete') {
                $classement->setDeleted($status);
                if ($status) {
                    $classement->setParent(false);
                }
                $change = true;
            } else if ($type === 'hide') {
                $classement->setHidden($status);
                if ($status) {
                    $classement->setParent(false);
                }
                $change = true;
            } else {
                return $this->error(CodeError::STATUS_ERROR, 'Status in error');
            }

            try {
                //save db data
                $entityManager = $doctrine->getManager();
                $entityManager->persist($classement);
                $entityManager->flush();

                $resultChange = [$classement];

                if ($change) {

                    // remove parent

                    $classementTemplate = $rep->findByTemplateParent($classement->getTemplateId());

                    if (count($classementTemplate)) {
                        $classementTemplate[0]->setParent(false);
                        $entityManager->persist($classementTemplate[0]);
                        $entityManager->flush();

                        $resultChange[] = $classementTemplate[0];
                    }

                    // first new parent

                    $classementTemplateFirst = $rep->findByTemplateFirst($classement->getTemplateId());

                    if (count($classementTemplateFirst)) {
                        $classementTemplateFirst[0]->setParent(true);
                        $entityManager->persist($classementTemplateFirst[0]);
                        $entityManager->flush();

                        $resultChange[] = $classementTemplateFirst[0];
                    }
                };

                return $this->OK($this->mapClassements($resultChange, true));
            } catch (Error $e) {
                return $this->error(
                    CodeError::DB_SAVE_REQUEST_ERROR,
                    'DB save error',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
