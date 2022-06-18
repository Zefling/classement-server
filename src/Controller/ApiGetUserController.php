<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetUserController extends AbstractApiController
{

    #[Route(
        '/api/profile/{id}',
        name: 'app_api_user_get',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => User::class,
            '_api_item_operations_name' => 'get_user',
        ],
    )]
    public function __invoke(string $id, ManagerRegistry $doctrine): Response
    {
        // control db
        $repUser = $doctrine->getRepository(User::class);
        $user = $repUser->findOneBy(['username' => $id, 'deleted' => false]);

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

                $userArray['classements'][] = $classementSubmit->toArray();
            }

            // remove data
            unset($userArray['password']);

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
