<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetClassementController extends AbstractApiController
{

    #[Route(
        '/api/classement/{id}',
        name: 'app_api_classement_get',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_item_operations_name' => 'get_publication',
        ],
    )]
    public function __invoke(string $id, ManagerRegistry $doctrine): Response
    {
        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classement = $rep->findOneBy(['rankingId' => $id]);

        if ($classement !== null) {

            // mapping
            $classementSubmit = new ClassementSubmit();
            $classementSubmit->setTemplateId($classement->getTemplateId());
            $classementSubmit->setRankingId($classement->getRankingId());
            $classementSubmit->setData($classement->getData());
            $classementSubmit->setBanner($classement->getBanner());
            $classementSubmit->setName($classement->getName());
            $classementSubmit->setGroupName($classement->getGroupName());

            // return updated data
            return $this->json(
                [
                    'message' => $classementSubmit->toArray(),
                    'code' => Response::HTTP_OK,
                    'status' => 'OK'
                ]
            );
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
