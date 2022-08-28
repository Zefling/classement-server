<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\TokenOauth;
use App\Entity\User;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;


#[AsController]
class ApiOAuthLoginController extends TokenInit
{

    #[Route(
        '/api/login/oauth',
        name: 'app_api_oauth_login',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => TokenOauth::class,
            '_api_collection_operations_name' => 'app_api_oauth_login',
        ],
    )]
    public function __invoke(
        Request $request,
        ManagerRegistry $doctrine,
    ): Response {

        $tokenService = new TokenOauth();
        $tokenService->mapFromArray($request->toArray());

        if (empty(trim($tokenService->getToken()))) {
            return $this->error(CodeError::TOKEN_NOT_FOUND, 'No token');
        }

        if (empty($tokenService->getService())) {
            return $this->error(CodeError::SERVICE_NOT_FOUND, 'No servuce');
        }

        $tokenRep = $doctrine->getRepository(Token::class);
        $tokenTmp = $tokenRep->findOneBy([
            'token' => $tokenService->getToken(),
            'role' => $tokenService->getService()
        ]);

        if (!$tokenTmp) {
            return $this->error(CodeError::INVALID_TOKEN, 'Invalide token', Response::HTTP_FORBIDDEN);
        }

        $userRep = $doctrine->getRepository(User::class);
        $user = $userRep->findOneBy(['id' => $tokenTmp->getUserId()]);

        if (!$user) {
            return $this->error(CodeError::USER_NOT_FOUND, 'User user found', Response::HTTP_FOUND);
        }

        try {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($tokenTmp);

            $token = $this->initToken($user, $doctrine, 'login', '1 week');

            return $this->ok([
                'user'  => $user->getUserIdentifier(),
                'token' => $token->getToken(),
            ]);
        } catch (UniqueConstraintViolationException $ex) {
            return $this->error(CodeError::DUPLICATE_CONTENT, $ex->getMessage());
        }
    }
}
