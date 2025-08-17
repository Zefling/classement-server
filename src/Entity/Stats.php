<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Controller\ApiAdminStatsController;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/admin/stats',
            name: 'app_api_stats_classements_get',
            controller: ApiAdminStatsController::class,
        ),
    ],
    paginationEnabled: true,
)]
class Stats {}
