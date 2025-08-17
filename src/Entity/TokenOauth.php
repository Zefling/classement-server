<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\ApiOAuthLoginController;
use App\Utils\EntityCommon;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/login/oauth',
            name: 'app_api_oauth_login',
            controller: ApiOAuthLoginController::class,
        ),
    ],
)]
class TokenOauth extends EntityCommon
{
    protected string $token;

    protected string $service;

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(string $service): self
    {
        $this->service = $service;

        return $this;
    }
}
