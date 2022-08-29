<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetClassementsTemplateController extends AbstractApiController
{

    #[Route(
        '/api/classements/template/{id}',
        name: 'app_api_classements_template_get',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_collection_operations_name' => 'app_api_classements_template_get',
        ],
    )]
    public function __invoke(string $id, ManagerRegistry $doctrine): Response
    {

        if (empty(trim($id))) {
            return $this->error(CodeError::TEMPLATE_NO_ID, 'No classement found with this paramters');
        }

        $classements = $doctrine->getRepository(Classement::class)->findByTemplate($id);

        $list = $this->mapClassements($classements);

        if (!empty($list)) {
            // return updated data
            return $this->OK($list);
        } else {
            return $this->error(
                CodeError::TEMPLATE_NOT_FOUND,
                'No classement found with this paramters',
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
