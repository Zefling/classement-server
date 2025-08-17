<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Classement;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiDeleteClassementController extends AbstractApiController implements TokenAuthenticatedController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_classement_delete';
    }

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
                $classement->setParent(false);

                //save db data
                $entityManager = $doctrine->getManager();
                $entityManager->persist($classement);
                $entityManager->flush();

                // remove parent

                $classementTemplate = $rep->findByTemplateParent($classement->getTemplateId());

                if (count($classementTemplate)) {
                    $classementTemplate[0]->setParent(false);
                    $entityManager->persist($classementTemplate[0]);
                    $entityManager->flush();
                }

                // first new parent

                $classementTemplateFirst = $rep->findByTemplateFirst($classement->getTemplateId());

                if (count($classementTemplateFirst)) {
                    $classementTemplateFirst[0]->setParent(true);
                    $entityManager->persist($classementTemplateFirst[0]);
                    $entityManager->flush();
                }

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
