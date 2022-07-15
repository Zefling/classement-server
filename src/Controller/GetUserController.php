<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;


class GetUserController extends AbstractApiController
{

    public function invoke(string $username, ManagerRegistry $doctrine): Response
    {
        // control db
        $repUser = $doctrine->getRepository(User::class);
        $user = $repUser->findOneBy(['username' => $username, 'deleted' => false]);

        if ($user !== null) {

            // control db
            $repClassement = $doctrine->getRepository(Classement::class);
            $classements = $repClassement->findBy(['User' => $user, 'deleted' => false, 'hide' => false]);

            $userArray = $user->toArray();
            $userArray['classements'] = $this->mapClassements($classements);

            // remove unnecessary data
            unset(
                $userArray['password'],
                $userArray['plainPassword'],
                $userArray['isValidated'],
                $userArray['email'],
                $userArray['deleted']
            );

            // return updated data
            return $this->OK($userArray);
        } else {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found', Response::HTTP_NOT_FOUND);
        }
    }
}
