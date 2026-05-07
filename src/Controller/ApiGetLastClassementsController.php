<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Entity\Classement;
use App\Entity\ClassementStats;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiGetLastClassementsController extends AbstractApiController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_classements_last';
    }

    public function __invoke(Request $request, ManagerRegistry $doctrine): Response
    {

        $limit = intval($request->query->get('limit'), 10);
        $limit = $limit ? $limit : 10;
        $limit = min(max($limit, 1), 15);
        $adult = $request->query->get('adult') === 'true';

        $classements = $doctrine->getRepository(Classement::class)->findLastTemplate($limit, $adult);

        // add total ranking by template
        $viewCounts = [];
        if (!empty($classements)) {
            $listTemplateIds = [];
            $listRankingIds = [];

            foreach ($classements as $classement) {
                $listTemplateIds[] = $classement->getTemplateId();
                $listRankingIds[] = $classement->getRankingId();
            }
            $counts = $doctrine->getRepository(Classement::class)->countByTemplateId($listTemplateIds, $adult);
            
            // Get view counts
            $statsRepo = $doctrine->getRepository(ClassementStats::class);
            $viewCounts = $statsRepo->getViewCounts($listRankingIds);

            foreach ($classements as $classement) {
                if (isset($counts[$classement->getTemplateId()])) {
                    $classement->setTemplateTotal($counts[$classement->getTemplateId()]);
                }
            }
        }

        $list = $this->mapClassements($classements) ?? [];
        
        // Add view counts to the list (always add the field, even if 0)
        if (!empty($list)) {
            foreach ($list as &$item) {
                $item['viewCount'] = $viewCounts[$item['rankingId']] ?? 0;
            }
        }

        // return updated data
        return $this->OK($list);
    }
}
