<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiAdminStatsController;

#[ApiResource(
    collectionOperations: [
        'get_stats' => [
            'method' => 'GET',
            'path' => '/admin/stats',
            'name' => 'app_api_classements_get',
            'controller' => ApiAdminStatsController::class,
        ],
    ],
    itemOperations: [],
    paginationEnabled: true,
)]
class Stats {}
