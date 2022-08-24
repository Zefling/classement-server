<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Entity\UserSingup;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiSignupValidateController extends AbstractApiController
{
    #[Route(
        '/api/signup/validity/{token}',
        name: 'app_api_signup_validity',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => UserSingup::class,
            '_api_item_operations_name' => 'app_api_signup_validity',
        ],
    )]
    public function __invoke(string $token, ManagerRegistry $doctrine): Response
    {

        if (!empty($token)) {
            $tokenRep = $doctrine->getRepository(Token::class);
            $token = $tokenRep->findOneBy(['token' => $token, 'role' => 'validity']);

            // test token validity
            if ($token !== null && new DateTime() <= $token->getValidity()) {

                $userRep = $doctrine->getRepository(User::class);
                $user = $userRep->findOneBy(['id' => $token->getUserId()]);

                if ($user) {
                    $user->setIsValidated(true);

                    $entityManager = $doctrine->getManager();
                    $entityManager->persist($user);
                    $entityManager->remove($token);
                    $entityManager->flush();

                    return $this->OK();
                } else {
                    return  $this->error(CodeError::USER_NOT_FOUND, 'User not found', Response::HTTP_NOT_FOUND);
                }
            } else {
                return  $this->error(CodeError::INVALID_TOKEN, 'Invalide Token', Response::HTTP_UNAUTHORIZED);
            }
        } else {
            return  $this->error(CodeError::TOKEN_NOT_FOUND, 'No token found', Response::HTTP_NOT_FOUND);
        }
    }
}
