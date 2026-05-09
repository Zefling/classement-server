<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\ClassementVoteSubmitStateProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/classement/{id}/vote',
            name: 'app_api_classement_vote',
            requirements: ['id' => '\S+'],
            priority: 10,
            processor: ClassementVoteSubmitStateProcessor::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
class ClassementVoteSubmitDto
{
    #[ApiProperty(
        description: 'Array of emoji vote types (or null/empty to remove all votes)',
        openapiContext: [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'example' => ['👍', '🥰'],
        ]
    )]
    public ?array $vote = null;

    #[ApiProperty(description: 'Action performed (created, updated, removed)', readable: true, writable: false)]
    public ?string $action = null;

    #[ApiProperty(
        description: 'Updated vote counts by emoji type',
        readable: true,
        writable: false,
        openapiContext: [
            'type' => 'object',
            'example' => ['👍' => 5, '🥰' => 3, '🤣' => 2],
            'additionalProperties' => ['type' => 'integer'],
        ]
    )]
    public array $votes = [];
}
