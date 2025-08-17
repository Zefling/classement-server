<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Token;
use App\Entity\User;
use App\Entity\UserPasswordChange;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiPasswordLostChangeController extends AbstractApiController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_password_lost_change';
    }

    public function __invoke(
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher
    ): Response {

        // mapping
        $pwChange = new UserPasswordChange();
        $pwChange->mapFromArray($request->toArray());

        if (!empty($pwChange->getToken())) {

            $userRep = $doctrine->getRepository(Token::class);
            $token = $userRep->findOneBy(['token' => $pwChange->getToken(), 'role' => 'password']);

            // test token validity
            if ($token !== null && new DateTime() <= $token->getValidity()) {

                $userRep = $doctrine->getRepository(User::class);
                $user = $userRep->findOneBy(['id' => $token->getUserId()]);

                if ($user) {
                    // test password
                    if (!empty($pwChange->getPassword()) && strlen($password = trim($pwChange->getPassword())) > 8) {
                        $hashedPassword = $passwordHasher->hashPassword(
                            $user,
                            $password
                        );
                        $user->setPassword($hashedPassword);

                        $entityManager = $doctrine->getManager();
                        $entityManager->persist($user);
                        $entityManager->remove($token);
                        $entityManager->flush();

                        return $this->OK();
                    } else {
                        return  $this->error(CodeError::PASSWORD_MISSING, 'No password or valid password');
                    }
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
