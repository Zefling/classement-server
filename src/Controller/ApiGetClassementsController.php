<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use Doctrine\Persistence\ManagerRegistry;
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
        $category = $request->query->get('category') ?? null;
        $mode = $request->query->get('mode') ?? null;
        $name = $request->query->get('name') ?? null;
        $page = $request->query->get('page') ?? 1;
        $tag = $request->query->get('tag') ?? null;
        $adult = $request->query->get('adult') === 'true';
        $all = $request->query->get('all') === 'true';
        $pageSize = is_numeric($request->query->get('size')) ? max(9, min(50, $request->query->get('size'))) ?? 24 : 24;

        $rep = $doctrine->getRepository(Classement::class);

        $count = $rep->countBySearchTemplateField(
            $name,
            $mode,
            $category,
            $tag,
            $adult,
            $all
        );

        if ($count > 0) {
            $classements = $rep->findBySearchTemplateField(
                $name,
                $mode,
                $category,
                $tag,
                $adult,
                $all,
                $page,
                $pageSize
            );

            // add total ranking by template
            if (!empty($classements)) {
                $listTemplateIds = [];

                foreach ($classements as $key => $classement) {
                    $listTemplateIds[] = $classement->getTemplateId();
                }
                $counts = $rep->countByTemplateId($listTemplateIds, $adult);

                foreach ($classements as $classement) {
                    if (isset($counts[$classement->getTemplateId()])) {
                        $classement->setTemplateTotal($counts[$classement->getTemplateId()]);
                    }
                }
            }

            $list = $this->mapClassements($classements);

            if (!empty($list)) {
                // return updated data
                return $this->OK([
                    'list' => $list,
                    'total' => $count
                ]);
            }
        }
        return $this->error(
            CodeError::CLASSEMENTS_NOT_FOUND,
            'No classement found with this parameters',
            Response::HTTP_NOT_FOUND
        );
    }
}
