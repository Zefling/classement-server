<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Theme;
use App\Entity\ThemeSubmit;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiDeleteThemeController extends AbstractApiController implements TokenAuthenticatedController
{

    #[Route(
        '/api/theme/{id}',
        name: 'app_api_theme_delete',
        methods: ['DELETE'],
        defaults: [
            '_api_resource_class' => ThemeSubmit::class,
            '_api_item_operations_name' => 'delete_publication',
        ],
    )]
    public function __invoke(#[CurrentUser] ?User $user, string $id, ManagerRegistry $doctrine): Response
    {
        if (null === $user) {
            return $this->error(CodeError::USER_NOT_FOUND, 'missing credentials', Response::HTTP_UNAUTHORIZED);
        }

        // control db
        $rep = $doctrine->getRepository(Theme::class);
        $theme = $rep->findOneBy(['themeId' => $id, 'deleted' => 0]);

        if ($theme !== null) {

            // if the UserId as same with the current user or if moderator
            if ($theme->getUser()->getId() === $user->getId() || $user->isModerator()) {
                $user->isUser();

                // mapping
                $theme->setDeleted(true);

                //save db data
                $entityManager = $doctrine->getManager();
                $entityManager->persist($theme);
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
            return $this->error(CodeError::THEME_NOT_FOUND, 'Theme not found', Response::HTTP_NOT_FOUND);
        }
    }
}
