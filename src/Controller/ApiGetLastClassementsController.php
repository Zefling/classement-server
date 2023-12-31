<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetLastClassementsController extends AbstractApiController
{

    #[Route(
        '/api/classements/last',
        name: 'app_api_classements_last',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_collection_operations_name' => 'app_api_classements_last',
        ],
    )]
    public function __invoke(Request $request, ManagerRegistry $doctrine): Response
    {

        $limit = intval($request->query->get('limit'), 10);
        $limit = $limit ? $limit : 10;
        $limit = min(max($limit, 1), 15);

        $classements = $doctrine->getRepository(Classement::class)->findLastTemplate($limit);

        // add total ranking by template
        if (!empty($classements)) {
            $listTemplateIds = [];

            foreach ($classements as $key => $classement) {
                $listTemplateIds[] = $classement->getTemplateId();
            }
            $counts = $doctrine->getRepository(Classement::class)->countByTemplateId($listTemplateIds);

            foreach ($classements as $classement) {
                if (isset($counts[$classement->getTemplateId()])) {
                    $classement->setTemplateTotal($counts[$classement->getTemplateId()]);
                }
            }
        }

        // return updated data
        return $this->OK($this->mapClassements($classements) ?? []);
    }
}
