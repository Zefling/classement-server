<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Classement;
use App\Entity\Stats;
use App\Entity\User;
use DateTime;
use DateTimeInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class ApiAdminStatsController extends AbstractApiController implements TokenAuthenticatedController
{

    #[Route(
        '/api/admin/stats',
        name: 'app_api_admin_classements',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => Stats::class,
            '_api_item_operations_name' => 'app_api_admin_classements',
        ],
    )]
    public function __invoke(#[CurrentUser] ?User $user, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!($user?->isAdmin())) {
            return $this->error(CodeError::USER_NO_PERMISSION, 'moderation role required', Response::HTTP_UNAUTHORIZED);
        }

        $target = $request->query->get('target') ?? 'user';
        $period = $request->query->get('period') ?? 'day';

        if ($target === 'classement') {
            $rep = $doctrine->getRepository(Classement::class);
        } else {
            $rep = $doctrine->getRepository(User::class);
        }

        $startDate = $request->query->get('startDate') ?? null;
        $endDate = $request->query->get('endDate') ?? null;

        $today = new DateTime();
        $oneYearAgo = (clone $today)->modify('-1 year');

        $startDate = $startDate !== null ? new DateTime($startDate) : $oneYearAgo;
        $endDate = $endDate !== null ? new DateTime($endDate) : $today;

        $this->correctDates($startDate, $endDate);

        if ($period === 'month') {
            $stats = $rep->getStatsByMonth($startDate, $endDate);
        } else if ($period === 'week') {
            $stats = $rep->getStatsByWeek($startDate, $endDate);
        } else {
            $stats = $rep->getStatsByDay($startDate, $endDate);
        }

        if (!empty($stats)) {
            if ($target === 'classement') {
                foreach ($stats as &$result) {
                    $result['deleted'] = (int) $result['deleted'];
                    $result['hide'] = (int) $result['hide'];
                    $result['parent'] = (int) $result['parent'];
                }
            } else {
                foreach ($stats as &$result) {
                    $result['deleted'] = (int) $result['deleted'];
                    $result['validated'] = (int) $result['validated'];
                }
            }

            return $this->OK([
                'stats' => $stats,
            ]);
        } else {
            return $this->error(CodeError::STATS_ERROR, 'Stats error', Response::HTTP_NOT_FOUND);
        }
    }


    private function correctDates(DateTimeInterface &$startDate, DateTimeInterface &$endDate)
    {
        $today = new DateTime();
        $oneMonthAgo = (clone $today)->modify('-1 month');

        // Correct the end date if it is after today
        if ($endDate > $today) {
            $endDate = $today;
        }

        // Correct the start date if it is after one month from today
        if ($startDate > $oneMonthAgo) {
            $startDate = $oneMonthAgo;
        }

        // Correct the start date if it is after or equal to the end date
        if ($startDate >= $endDate) {
            $startDate = (clone $endDate)->modify('-1 month');
        }

        // Calculate the interval between startDate and endDate
        $interval = $startDate->diff($endDate);
        $months = $interval->y * 12 + $interval->m;

        // Correct the start date if the interval is less than 1 month
        if ($months < 1) {
            $startDate = (clone $endDate)->modify('-1 month');
        }

        // Correct the start date if the interval is more than 1 year
        if ($months > 12) {
            $startDate = (clone $endDate)->modify('-1 year');
        }
    }
}
