<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\ApiPasswordLostChangeController;
use App\Utils\EntityCommon;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/password-change',
            name: 'app_api_password_lost_change',
            controller: ApiPasswordLostChangeController::class,
        ),
    ],
)]
class UserPasswordChange extends EntityCommon
{
    protected string $token;

    protected string $password;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
}
