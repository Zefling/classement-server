<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserLogin;
use App\EventSubscriber\TokenSubscriber;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsController]
class ApiUserUpdateController extends AbstractApiController implements TokenAuthenticatedController
{
    public function __construct(private TokenSubscriber $tokenSubscriber)
    {
    }

    #[Route(
        '/api/user/update',
        name: 'app_api_user_update',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => UserLogin::class,
            '_api_item_operation_name' => 'app_api_user_update',
        ],
    )]
    public function __invoke(
        #[CurrentUser] ?User $user,
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if (null === $user) {
            return $this->error('missing credentials', Response::HTTP_UNAUTHORIZED);
        }

        // mapping
        $content = $request->toArray();

        $userRep = $doctrine->getRepository(User::class);

        // username

        if ($content['username'] !== $user->getUsername()) {

            if (!empty($content['username']) && !empty($username = trim($content['username']))) {

                // test if login already exist
                $userLogin =  $userRep->findOneBy(['username' => $username]);

                if ($userLogin === null) {
                    $user->setUsername($username);
                } else {
                    return $this->error(CodeError::LOGIN_ALREADY_EXISTS, 'This username already exists.');
                }
            } else {
                return $this->error(CodeError::LOGIN_MISSING, 'No username');
            }
        }

        // password

        if (!empty($content['password'])) {
            if (strlen($password = trim($content['password'])) > 8) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $password
                );
                $user->setPassword($hashedPassword);
            } else {
                return $this->error(CodeError::PASSWORD_MISSING, 'No password or valid password');
            }
        }

        // email

        if ($content['email'] !== $user->getEmail()) {

            if (!empty($content['email']) && filter_var($email = trim($content['email']), FILTER_VALIDATE_EMAIL)) {

                // test if email already exist
                $userEmail =  $userRep->findOneBy(['email' => $email]);

                if ($userEmail === null) {
                    $user->setEmail($email);
                } else {
                    return $this->error(CodeError::EMAIL_ALREADY_EXISTS, 'This email already exists.');
                }
            } else {
                return $this->error(CodeError::EMAIL_MISSING, 'No email or valid email');
            }
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return $$this->json([
            'code' => Response::HTTP_OK,
            'status' => 'OK'
        ]);
    }
}
