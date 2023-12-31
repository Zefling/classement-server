<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Token;
use App\Entity\User;
use App\EventSubscriber\TokenSubscriber;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

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
    public function __invoke(#[CurrentUser] ?User $user, ManagerRegistry $doctrine): Response
    {
        if ($user !== null) {
            try {
                $tokenRep = $doctrine->getRepository(Token::class);
                $tokenRep->removeByUser($user);

                return $this->OK();
            } catch (UniqueConstraintViolationException $ex) {
                return $this->error(CodeError::TOKEN_NOT_FOUND, 'Token not found', Response::HTTP_NOT_FOUND);
            }
        } else {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found', Response::HTTP_NOT_FOUND);
        }
    }
}
