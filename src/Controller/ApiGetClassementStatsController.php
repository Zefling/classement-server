<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Classement;
use App\Entity\ClassementStatsDaily;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiGetClassementStatsController extends AbstractApiController
{
    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_classement_stats_get';
    }

    public function __invoke(
        string $id,
        ManagerRegistry $doctrine,
        Request $request
    ): Response {
        // Get the authenticated user
        /** @var User|null $user */
        $user = $this->getUser();

        // Get the classement
        $classementRepo = $doctrine->getRepository(Classement::class);
        $classement = $classementRepo->findByIdOrlinkName($id);

        if ($classement === null) {
            return $this->error(
                CodeError::CLASSEMENT_NOT_FOUND,
                'Classement not found',
                Response::HTTP_NOT_FOUND
            );
        }

        // Check permissions: must be owner or admin
        $isOwner = $user && $classement->getUser()->getId() === $user->getId();
        $isAdmin = $user && in_array('ROLE_ADMIN', $user->getRoles());

        if (!$isOwner && !$isAdmin) {
            return $this->error(
                CodeError::CLASSEMENT_UNAUTHORIZED,
                'You are not authorized to view statistics for this classement',
                Response::HTTP_FORBIDDEN
            );
        }

        // Get query parameters
        $period = $request->query->get('period', '7d'); // 7d, 30d, 90d, 1y, all
        $granularity = $request->query->get('granularity', 'day'); // day, week, month

        // Calculate date range based on period
        $endDate = new \DateTime();
        $startDate = $this->calculateStartDate($period, $endDate);

        // Get daily stats
        $dailyRepo = $doctrine->getRepository(ClassementStatsDaily::class);
        $dailyStats = $dailyRepo->getViewCountsForPeriod(
            $classement->getRankingId(),
            $startDate,
            $endDate
        );

        // Aggregate data based on granularity
        $aggregatedStats = $this->aggregateStats($dailyStats, $granularity, $startDate, $endDate);

        // Get total stats
        $totalViews = $dailyRepo->getTotalViewCount($classement->getRankingId());

        // Prepare response
        $response = [
            'rankingId' => $classement->getRankingId(),
            'name' => $classement->getName(),
            'period' => $period,
            'granularity' => $granularity,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'totalViews' => $totalViews,
            'periodViews' => array_sum($dailyStats),
            'stats' => $aggregatedStats
        ];

        return $this->json($response);
    }

    /**
     * Calculate start date based on period
     */
    private function calculateStartDate(string $period, \DateTime $endDate): \DateTime
    {
        $startDate = clone $endDate;

        switch ($period) {
            case '7d':
                $startDate->modify('-7 days');
                break;
            case '30d':
                $startDate->modify('-30 days');
                break;
            case '90d':
                $startDate->modify('-90 days');
                break;
            case '1y':
                $startDate->modify('-1 year');
                break;
            case 'all':
                $startDate->modify('-10 years'); // Arbitrary far date
                break;
            default:
                // Try to parse custom format like "14d" or "6m"
                if (preg_match('/^(\d+)([dmy])$/', $period, $matches)) {
                    $value = (int)$matches[1];
                    $unit = $matches[2];
                    
                    switch ($unit) {
                        case 'd':
                            $startDate->modify("-{$value} days");
                            break;
                        case 'm':
                            $startDate->modify("-{$value} months");
                            break;
                        case 'y':
                            $startDate->modify("-{$value} years");
                            break;
                    }
                } else {
                    // Default to 7 days
                    $startDate->modify('-7 days');
                }
        }

        return $startDate;
    }

    /**
     * Aggregate stats based on granularity
     */
    private function aggregateStats(
        array $dailyStats,
        string $granularity,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        if ($granularity === 'day') {
            // Fill missing days with 0
            return $this->fillMissingDays($dailyStats, $startDate, $endDate);
        }

        if ($granularity === 'week') {
            return $this->aggregateByWeek($dailyStats, $startDate, $endDate);
        }

        if ($granularity === 'month') {
            return $this->aggregateByMonth($dailyStats, $startDate, $endDate);
        }

        // Default to day
        return $this->fillMissingDays($dailyStats, $startDate, $endDate);
    }

    /**
     * Fill missing days with 0 views
     */
    private function fillMissingDays(
        array $dailyStats,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        $result = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $result[] = [
                'date' => $dateKey,
                'views' => $dailyStats[$dateKey] ?? 0
            ];
            $current->modify('+1 day');
        }

        return $result;
    }

    /**
     * Aggregate by week
     */
    private function aggregateByWeek(
        array $dailyStats,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        $result = [];
        $current = clone $startDate;
        
        // Start from Monday of the week
        $current->modify('monday this week');

        while ($current <= $endDate) {
            $weekStart = clone $current;
            $weekEnd = clone $current;
            $weekEnd->modify('+6 days');

            $weekViews = 0;
            $tempDate = clone $weekStart;

            while ($tempDate <= $weekEnd && $tempDate <= $endDate) {
                $dateKey = $tempDate->format('Y-m-d');
                $weekViews += $dailyStats[$dateKey] ?? 0;
                $tempDate->modify('+1 day');
            }

            $result[] = [
                'weekStart' => $weekStart->format('Y-m-d'),
                'weekEnd' => min($weekEnd, $endDate)->format('Y-m-d'),
                'views' => $weekViews
            ];

            $current->modify('+7 days');
        }

        return $result;
    }

    /**
     * Aggregate by month
     */
    private function aggregateByMonth(
        array $dailyStats,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        $result = [];
        $current = clone $startDate;
        
        // Start from first day of the month
        $current->modify('first day of this month');

        while ($current <= $endDate) {
            $monthStart = clone $current;
            $monthEnd = clone $current;
            $monthEnd->modify('last day of this month');

            $monthViews = 0;
            $tempDate = clone $monthStart;

            while ($tempDate <= $monthEnd && $tempDate <= $endDate) {
                if ($tempDate >= $startDate) {
                    $dateKey = $tempDate->format('Y-m-d');
                    $monthViews += $dailyStats[$dateKey] ?? 0;
                }
                $tempDate->modify('+1 day');
            }

            if ($monthStart >= $startDate || $monthEnd >= $startDate) {
                $result[] = [
                    'month' => $current->format('Y-m'),
                    'monthName' => $current->format('F Y'),
                    'views' => $monthViews
                ];
            }

            $current->modify('first day of next month');
        }

        return $result;
    }
}
