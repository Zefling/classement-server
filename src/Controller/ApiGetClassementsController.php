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
class ApiGetClassementsController extends AbstractApiController
{

    #[Route(
        '/api/classements',
        name: 'app_api_classements_get',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_collection_operations_name' => 'get_publications',
        ],
    )]
    public function __invoke(Request $request, ManagerRegistry $doctrine): Response
    {

        // control db
        $name = $request->query->get('name');
        $page = $request->query->get('page') ?? 1;
        $classements = $doctrine->getRepository(Classement::class)->findByNameTemplateField($name, $page);


        if ($classements !== null) {

            $list = [];

            foreach ($classements as $classement) {

                // mapping
                $classementSubmit = new ClassementSubmit();
                $classementSubmit->setTemplateId($classement->getTemplateId());
                $classementSubmit->setRankingId($classement->getRankingId());
                $classementSubmit->setData($classement->getData());
                $classementSubmit->setBanner($classement->getBanner());
                $classementSubmit->setName($classement->getName());
                $classementSubmit->setGroupName($classement->getGroupName());

                $list[] = $classementSubmit->toArray();
            }
            print_r($list);
            die();

            // return updated data
            return new JsonResponse(
                [
                    'message' => $list,
                    'code' => Response::HTTP_OK,
                    'status' => 'OK'
                ]
            );
        } else {
            return $this->error(
                CodeError::CLASSEMENTS_NOT_FOUND,
                'No classement found with this paramters',
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
