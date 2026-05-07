<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\CodeError;
use App\Repository\ClassementStatsRepository;
use App\Service\ViewTracker;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiGetClassementIncrementViewCountController extends AbstractApiController
{
    public static function getName(): string
    {
        return 'app_api_classement_view_count_increment';
    }

    public function __invoke(
        string $id,
        ManagerRegistry $doctrine,
        ViewTracker $viewTracker
    ): Response {
        if (empty($id) || !trim($id)) {
            return $this->error(CodeError::INVALID_DATA, 'rankingId is required');
        }

        $rankingId = trim($id);
        $statsRepository = $doctrine->getRepository(\App\Entity\ClassementStats::class);
        
        if (!$statsRepository instanceof ClassementStatsRepository) {
            return $this->error(CodeError::STATS_ERROR, 'Repository not found');
        }

        // Increment view count only if not viewed recently by this user
        if ($viewTracker->shouldCountView($rankingId)) {
            $statsRepository->incrementViewCount($rankingId);
        }

        $viewCount = $statsRepository->getViewCount($rankingId);

        return $this->json([
            'viewCount' => $viewCount
        ], Response::HTTP_OK);
    }
}
