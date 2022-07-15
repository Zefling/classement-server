<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetCategoriesHomeController extends AbstractApiController
{

    #[Route(
        '/api/categories/home',
        name: 'app_api_group_home_get',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_item_operations_name' => 'app_api_group_home_get',
        ],
    )]
    public function __invoke(ManagerRegistry $doctrine): Response
    {
        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classements = $rep->findByTemplateCategory();
        $classementSubmit = $this->mapClassements($classements);

        if ($classementSubmit !== null) {

            // return updated data
            return $this->OK($classementSubmit);
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
