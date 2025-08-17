<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Theme;
use App\Utils\Utils;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiGetThemeController extends AbstractApiController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_theme_get';
    }

    public function __invoke(
        string $id,
        ManagerRegistry $doctrine,
        Request $request
    ): Response {

        $idHistory = $request->query->get('history') ?? null;
        $themeHistory = null;

        // control db
        $rep = $doctrine->getRepository(Theme::class);
        $theme = $rep->findOneBy(['themeId' => $id, 'deleted' => 0]);

        if ($theme !== null) {

            $themeSubmit = $this->mapTheme($theme, true);
            unset($themeSubmit['deleted']);

            if ($idHistory !== null && $themeHistory !== null) {
                // mapping
                $themeSubmit['data']        = Utils::formatData($themeHistory->getData());
                $themeSubmit['name']        = $themeHistory->getName();

                if ($themeSubmit['dateCreate'] !== $themeHistory->getDate()) {
                    $themeSubmit['dateChange'] = $themeHistory->getDate();
                }
            }

            // return updated data
            return $this->OK($themeSubmit);
        } else {
            return $this->error(CodeError::THEME_NOT_FOUND, 'Theme not found', Response::HTTP_NOT_FOUND);
        }
    }
}
