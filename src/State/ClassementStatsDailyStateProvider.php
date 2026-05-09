<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Enum\CodeError;
use App\Entity\Classement;
use App\Entity\ClassementStatsDaily;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

use App\State\AbstractStateProvider;
use Symfony\Component\HttpFoundation\Response;

class ClassementStatsDailyStateProvider extends AbstractStateProvider implements ProviderInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private Security $security,
        private RequestStack $requestStack,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $id = $uriVariables['id'] ?? null;
        $user = $this->security->getUser();

        $classementRepo = $this->doctrine->getRepository(Classement::class);
        $classement = $classementRepo->findByIdOrlinkName($id);

        if ($classement === null) {
            return $this->error(
                CodeError::CLASSEMENT_NOT_FOUND,
                'Classement not found',
                Response::HTTP_NOT_FOUND
            );
        }

        $isOwner = $user && $classement->getUser()->getId() === $user->getId();
        $isAdmin = $user && in_array('ROLE_ADMIN', $user->getRoles());

        if (!$isOwner && !$isAdmin) {
            return $this->error(
                CodeError::CLASSEMENT_UNAUTHORIZED,
                'You are not authorized to view statistics for this classement',
                Response::HTTP_FORBIDDEN
            );
        }

        $request = $this->requestStack->getCurrentRequest();
        $period = $request?->query->get('period', '7d');
        $granularity = $request?->query->get('granularity', 'day');

        $endDate = new \DateTime();
        $startDate = $this->calculateStartDate($period, $endDate);

        $dailyRepo = $this->doctrine->getRepository(ClassementStatsDaily::class);
        $dailyStats = $dailyRepo->getViewCountsForPeriod(
            $classement->getRankingId(),
            $startDate,
            $endDate
        );

        $aggregatedStats = $this->aggregateStats($dailyStats, $granularity, $startDate, $endDate);
        $totalViews = $dailyRepo->getTotalViewCount($classement->getRankingId());

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

        return $this->OK($response);
    }

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
                $startDate->modify('-10 years');
                break;
            default:
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
                    $startDate->modify('-7 days');
                }
        }

        return $startDate;
    }

    private function aggregateStats(
        array $dailyStats,
        string $granularity,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        if ($granularity === 'day') {
            return $this->fillMissingDays($dailyStats, $startDate, $endDate);
        }

        if ($granularity === 'week') {
            return $this->aggregateByWeek($dailyStats, $startDate, $endDate);
        }

        if ($granularity === 'month') {
            return $this->aggregateByMonth($dailyStats, $startDate, $endDate);
        }

        return $this->fillMissingDays($dailyStats, $startDate, $endDate);
    }

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

    private function aggregateByWeek(
        array $dailyStats,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        $result = [];
        $current = clone $startDate;

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

    private function aggregateByMonth(
        array $dailyStats,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        $result = [];
        $current = clone $startDate;

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
