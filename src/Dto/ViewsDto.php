<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\ViewsStateProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/classement/{id}/views',
            name: 'app_api_classement_view_count_increment',
            requirements: ['id' => '\S+'],
            processor: ViewsStateProcessor::class,
            provider: ViewsStateProcessor::class,
            read: false,
            deserialize: false,
            priority: 10,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
class ViewsDto
{
    public int $viewCount;
}
