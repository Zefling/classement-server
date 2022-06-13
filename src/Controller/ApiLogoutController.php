<?php

namespace App\Controller;

use App\Entity\Token;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\EventSubscriber\TokenSubscriber;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsController]
class ApiLogoutController extends AbstractApiController implements TokenAuthenticatedController
{
    public function __construct(private TokenSubscriber $tokenSubscriber)
    {
    }

    #[Route(
        '/api/logout',
        name: 'app_api_logout',
        methods: ['DELETE'],
    )]
    public function __invoke(ManagerRegistry $doctrine, UserInterface $user): Response
    {
        if ($user instanceof User) {
            try {
                $tokenRep = $doctrine->getRepository(Token::class);
                $tokenRep->removeByUser($user);

                return $this->json([
                    'code' => Response::HTTP_OK,
                    'status' => 'OK'
                ]);
            } catch (UniqueConstraintViolationException $ex) {
                return $this->error(CodeError::TOKEN_NOT_FOUND, 'Token not found');
            }
        } else {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found');
        }
    }
}
