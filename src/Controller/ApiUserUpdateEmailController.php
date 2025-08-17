<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Controller\Common\TokenAuthenticatedController;
use Symfony\Component\HttpFoundation\Response;
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
    public function __construct(private TokenSubscriber $tokenSubscriber) {}

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_user_update_mail';
    }

    public function __invoke(
        #[CurrentUser] ?User $user,
        Request $request,
        ManagerRegistry $doctrine,
    ): Response {
        if (null === $user) {
            return $this->error(CodeError::USER_MISSING_CREDENTIALS, 'Missing credentials', Response::HTTP_UNAUTHORIZED);
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
                $userEmailTest =  $userRep->findOneBy(['email' => $userEmail->getEmailNew()]);

                if ($userEmailTest === null) {
                    $user->setEmail($email);

                    $entityManager = $doctrine->getManager();
                    $entityManager->persist($user);
                    $entityManager->flush();

                    return $this->OK();
                } else {
                    return $this->error(
                        CodeError::EMAIL_ALREADY_EXISTS,
                        'This email already exists.',
                        Response::HTTP_CONFLICT
                    );
                }
            } else {
                return $this->error(CodeError::EMAIL_MISSING, 'No email or valid email.');
            }
        } else {
            return $this->error(
                CodeError::EMAIL_NO_MATCHING,
                'That is not the current email.',
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
