<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiPasswordLostChangeController;
use App\Utils\EntityCommon;

#[ApiResource(
    collectionOperations: [
        'app_api_password_lost_change' => [
            'method' => 'POST',
            'path' => '/password-change',
            'name' => 'app_api_password_lost_change',
            'controller' => ApiPasswordLostChangeController::class,
        ],
    ],
    itemOperations: []
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
