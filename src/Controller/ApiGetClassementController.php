<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

        // mapping
        $classementSubmit = new ClassementSubmit();

        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classement = $rep->findOneBy(['rankingId' => $id]);

        if ($classement !== null) {

            $classementSubmit->setTemplateId($classement->getTemplateId());
            $classementSubmit->setRankingId($classement->getRankingId());
            $classementSubmit->setData($classement->getData());
            $classementSubmit->setBanner($classement->getBanner());
            $classementSubmit->setName($classement->getName());
            $classementSubmit->setGroupName($classement->getGroupName());
            $classementSubmit->setParentId($classement->getParentId());

            // return updated data
            return new JsonResponse(
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
