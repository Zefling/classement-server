<?php

namespace App\Controller;

use App\Entity\Token;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserLogin;
use App\EventSubscriber\TokenSubscriber;
use DateInterval;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[AsController]
class ApiLoginController extends AbstractApiController
{
    public function __construct(private TokenSubscriber $tokenSubscriber)
    {
    }

    #[Route(
        '/api/login',
        name: 'app_api_login',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => UserLogin::class,
            '_api_collection_operations_name' => 'app_api_login',
        ],
    )]
    public function __invoke(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher): Response
    {

        $userLogin = new UserLogin();
        $userLogin->mapFromArray($request->toArray());

        if (empty(trim($userLogin->getUsername()))) {
            return $this->error(CodeError::LOGIN_MISSING, 'No username');
        }

        if (empty($userLogin->getPassword())) {
            return $this->error(CodeError::PASSWORD_MISSING, 'No password');
        }

        $userRep = $doctrine->getRepository(User::class);
        $user = $userRep->findOneBy(['username' => $userLogin->getUsername()]);

        if ($user === null) {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found');
        }

        $valid = $passwordHasher->isPasswordValid(
            $user,
            $userLogin->getPassword()
        );

        if (!$valid) {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found pw');
        }

        try {
            $tokenRep = $doctrine->getRepository(Token::class);
            $token = $tokenRep->findOneBy(['userId' => $user->getId()]);

            if ($token === null) {
                $token = new Token($user);
            } else {
                $date = $token->getDate();
                if ($date !== null) {
                    $date->add(new DateInterval("P1W"));
                    if ($date->getTimestamp() - (new DateTime())->getTimestamp() < 0) {
                        $token->reset();
                    } else {
                        $token->setDate(new DateTime());
                    }
                } else {
                    $token->reset();
                }
            }

            $entityManager = $doctrine->getManager();
            $entityManager->persist($token);
            $entityManager->flush();

            return new JsonResponse([
                'message' => [
                    'user'  => $user->getUserIdentifier(),
                    'token' => $token->getToken(),
                ],
                'code' => Response::HTTP_OK,
                'status' => 'OK'
            ]);
        } catch (UniqueConstraintViolationException $ex) {
            return $this->error(CodeError::DUPLICATE_CONTENT, $ex->getMessage());
        }
    }
}
