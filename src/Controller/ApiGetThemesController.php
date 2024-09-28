<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Theme;
use App\Entity\ThemeSubmit;
use Doctrine\Persistence\ManagerRegistry;
use Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetThemesController extends AbstractApiController
{

    #[Route(
        '/api/themes',
        name: 'app_api_themes_get',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => ThemeSubmit::class,
            '_api_collection_operations_name' => 'get_publications',
        ],
    )]
    public function __invoke(Request $request, ManagerRegistry $doctrine): Response
    {

        // control db
        $mode = $request->query->get('mode') ?? null;
        $name = $request->query->get('name') ?? null;
        $page = $request->query->get('page') ?? 1;
        try {
            $pageSize = max(9, min(50, $request->query->get('size'))) ?? 24;
        } catch (Error $ex) {
            $pageSize = 24;
        }

        $rep = $doctrine->getRepository(Theme::class);

        $count = $rep->countBySearchField(
            $name,
            $mode,
        );

        if ($count > 0) {
            $themes = $rep->findBySearchField(
                $name,
                $mode,
                $page,
                $pageSize
            );

            $list = $this->mapThemes($themes);

            if (!empty($list)) {
                // return updated data
                return $this->OK([
                    'list' => $list,
                    'total' => $count
                ]);
            }
        }
        return $this->error(
            CodeError::THEMES_NOT_FOUND,
            'No theme found with this parameters',
            Response::HTTP_NOT_FOUND
        );
    }
}
