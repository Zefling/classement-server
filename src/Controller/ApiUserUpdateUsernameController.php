<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Controller\Common\TokenAuthenticatedController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserLogin;
use App\EventSubscriber\TokenSubscriber;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiUserUpdateUsernameController extends AbstractApiController implements TokenAuthenticatedController
{
    public function __construct(private TokenSubscriber $tokenSubscriber)
    {
    }

    #[Route(
        '/api/user/update/username',
        name: 'app_api_user_update_username',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => UserLogin::class,
            '_api_collection_operations_name' => 'app_api_user_update_username',
        ],
    )]
    public function __invoke(
        #[CurrentUser] ?User $user,
        Request $request,
        ManagerRegistry $doctrine,
    ): Response {
        if (null === $user) {
            return $this->error(CodeError::USER_MISSING_CREDENTIALS, 'Missing credentials', Response::HTTP_UNAUTHORIZED);
        }

        // mapping
        $userLogin = new UserLogin();
        $userLogin->mapFromArray($request->toArray());

        $userRep = $doctrine->getRepository(User::class);

        // login
        if ($userLogin->getUsername() !== $user->getUsername()) {

            $login = trim($userLogin->getUsername());

            // test if login already exist
            $userLoginTest =  $userRep->findOneBy(['username' => $userLogin->getUsername()]);

            if ($userLoginTest === null) {
                $user->setUsername($login);

                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->OK();
            } else {
                return $this->error(
                    CodeError::USERNAME_ALREADY_EXISTS,
                    'This username already exists.',
                    Response::HTTP_CONFLICT
                );
            }
        } else {
            return $this->error(
                CodeError::USERNAME_IS_SAME,
                'This username is the same.',
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
