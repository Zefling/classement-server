<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use App\State\ViewsStateProcessor;

#[ApiResource(
    operations: [
        new Put(
            uriTemplate: '/classement/{id}/views',
            name: 'app_api_classement_view_count_increment',
            requirements: ['id' => '\S+'],
            processor: ViewsStateProcessor::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
class ViewsDto
{
    public int $viewCount;
}
