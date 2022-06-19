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
            $classements = $repClassement->findAll(['User' => $user, 'deleted' => false, 'hide' => false]);

            $userArray = $user->toArray();
            $userArray['classements'] = [];
            foreach ($classements as $classement) {

                // mapping
                $classementSubmit = new ClassementSubmit();
                $classementSubmit->setTemplateId($classement->getTemplateId());
                $classementSubmit->setRankingId($classement->getRankingId());
                $classementSubmit->setData($classement->getData());
                $classementSubmit->setBanner($classement->getBanner());
                $classementSubmit->setName($classement->getName());
                $classementSubmit->setGroupName($classement->getGroupName());
                $classementSubmit->setDateCreate($classement->getDateCreate());
                $classementSubmit->setDateChange($classement->getDateChange());

                $userArray['classements'][] = $classementSubmit->toArray();
            }

            // remove data
            unset($userArray['password']);
            unset($userArray['plainPassword']);
            unset($userArray['isValidated']);
            unset($userArray['email']);
            unset($userArray['roles']);
            unset($userArray['deleted']);

            // return updated data
            return $this->json(
                [
                    'message' => $userArray,
                    'code' => Response::HTTP_OK,
                    'status' => 'OK'
                ]
            );
        } else {
            return $this->error(CodeError::USER_NOT_FOUND, 'User not found', Response::HTTP_NOT_FOUND);
        }
    }
}
