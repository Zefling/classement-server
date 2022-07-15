<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserEmail;
use App\EventSubscriber\TokenSubscriber;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiUserUpdateEmailController extends AbstractApiController implements TokenAuthenticatedController
{
    public function __construct(private TokenSubscriber $tokenSubscriber)
    {
    }

    #[Route(
        '/api/user/update/email',
        name: 'app_api_user_update_mail',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => UserEmail::class,
            '_api_item_operation_name' => 'app_api_user_update_mail',
        ],
    )]
    public function __invoke(
        #[CurrentUser] ?User $user,
        Request $request,
        ManagerRegistry $doctrine,
    ): Response {
        if (null === $user) {
            return $this->error('missing credentials', Response::HTTP_UNAUTHORIZED);
        }

        // mapping
        $userEmail = new UserEmail();
        $userEmail->mapFromArray($request->toArray());

        $userRep = $doctrine->getRepository(User::class);

        // email

        if ($userEmail->getEmailOld() === $user->getEmail()) {

            if (
                !empty($userEmail->getEmailNew()) &&
                filter_var($email = trim($userEmail->getEmailNew()), FILTER_VALIDATE_EMAIL)
            ) {

                // test if email already exist
                $userEmail =  $userRep->findOneBy(['email' => $userEmail->getEmailNew()]);

                if ($userEmail === null) {
                    $user->setEmail($email);

                    $entityManager = $doctrine->getManager();
                    $entityManager->persist($user);
                    $entityManager->flush();

                    return $this->OK();
                } else {
                    return $this->error(CodeError::EMAIL_ALREADY_EXISTS, 'This email already exists.');
                }
            } else {
                return $this->error(CodeError::EMAIL_MISSING, 'No email or valid email.');
            }
        } else {
            return $this->error(CodeError::EMAIL_NO_MATCHING, 'That is not the current email.');
        }
    }
}
