<?php

namespace App\Controller;

use App\Enum\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Classement;
use App\Entity\ClassementStats;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiGetCategoriesHomeController extends AbstractApiController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_group_home_get';
    }

    public function __invoke(Request $request, ManagerRegistry $doctrine): Response
    {
        $adult = $request->query->get('adult') === 'true';

        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classements = $rep->findByTemplateCategory($adult);

        $viewCounts = [];
        if (!empty($classements)) {
            // for categories list
            $counts = $rep->countByCategories($adult);
            
            // Get view counts
            $listRankingIds = [];
            foreach ($classements as $classement) {
                $listRankingIds[] = $classement->getRankingId();
            }
            $statsRepo = $doctrine->getRepository(ClassementStats::class);
            $viewCounts = $statsRepo->getViewCounts($listRankingIds);

            foreach ($classements as $classement) {
                if ($counts[$classement->getCategory()->value]) {
                    $classement->setTemplateTotal($counts[$classement->getCategory()->value]);
                }
            }
        }

        $classementSubmit = $this->mapClassements($classements);
        
        // Add view counts to the list (always add the field, even if 0)
        if (!empty($classementSubmit)) {
            foreach ($classementSubmit as &$item) {
                $item['viewCount'] = $viewCounts[$item['rankingId']] ?? 0;
            }
        }

        if ($classementSubmit !== null) {

            // return updated data
            return $this->OK($classementSubmit);
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
