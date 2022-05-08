<?php

namespace App\Controller;

use App\Entity\Token;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\EventSubscriber\TokenSubscriber;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsController]
class ApiLoginController extends AbstractApiController implements TokenAuthenticatedController
{
    public function __construct(private TokenSubscriber $tokenSubscriber)
    {
    }

    #[Route(
        '/api/login',
        name: 'app_api_login',
        methods: ['POST']
    )]
    public function index(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();

        if (!empty($content['username']) && !empty($username = trim($content['username']))) {
            $user->setUsername($username);
        } else {
            return $this->error(CodeError::LOGIN_MISSING, 'No username');
        }

        if (!empty($content['password']) && strlen($password = trim($content['password'])) > 8) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);
        } else {
            return  $this->error(CodeError::PASSWORD_MISSING, 'No password');
        }
        $userRep = $doctrine->getRepository(User::class);
        $user = $userRep->findOneBy([
            'username' => $user->getUsername(),
            'password' => $user->getPassword(),
        ]);
        if ($user === null) {
            return  $this->error(CodeError::USER_NOT_FOUND, 'User not found');
        }

        $token = new Token($user->id);

        try {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($token);
            $entityManager->flush();

            return $this->json([
                'message' => [
                    'user'  => $user->getUserIdentifier(),
                    'token' => $token,
                ],
                'code' => Response::HTTP_OK,
                'status' => 'OK'
            ]);
        } catch (UniqueConstraintViolationException $ex) {
            return $this->error(CodeError::DUPLICATE_CONTENT, $ex->getMessage());
        }
    }

}
