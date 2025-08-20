<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Token;
use App\Entity\User;
use App\EventSubscriber\TokenSubscriber;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiLogoutController extends AbstractApiController implements TokenAuthenticatedController
{
    public function __construct(private TokenSubscriber $tokenSubscriber) {}

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_logout';
    }

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
