<?php

namespace App\Controller\Common;

use App\Controller\Common\CodeError;
use App\Entity\Classement;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;


class GetUserController extends AbstractApiController
{

    public function invoke(string $username, ManagerRegistry $doctrine, bool $hidden = true): Response
    {
        // control db
        $repUser = $doctrine->getRepository(User::class);
        $user = $repUser->findOneBy(['username' => $username, 'deleted' => false]);

        if ($user !== null) {

            // control db
            $repClassement = $doctrine->getRepository(Classement::class);
            $classements = $repClassement->findBy([
                'User'    => $user,
                'deleted' => false,
                ...($hidden ? [] : ['hidden' => false])
            ]);

            // add total ranking by template
            if (!empty($classements)) {
                $listTemplateIds = [];
                foreach ($classements as $classement) {
                    $listTemplateIds[] = $classement->getTemplateId();
                }
                $counts = $doctrine->getRepository(Classement::class)->countByTemplateId($listTemplateIds);

                foreach ($classements as $classement) {
                    if (isset($counts[$classement->getTemplateId()])) {
                        $classement->setTemplateTotal($counts[$classement->getTemplateId()]);
                    }
                }
            }

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
