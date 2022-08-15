<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiSignupController;
use App\Controller\ApiSignupValidateController;
use App\Utils\EntityCommon;

#[ApiResource(
    collectionOperations: [
        'app_api_signup' => [
            'method' => 'POST',
            'path' => '/{_locale<%app.supported_locales%>}/signup',
            'name' => 'app_api_signup',
            'controller' => ApiSignupController::class
        ],
    ],
    itemOperations: [
        'app_api_signup_validity' => [
            'method' => 'GET',
            'path' => '/signup/validity/{token}',
            'requirements' => ['token' => '\s+'],
            'name' => 'app_api_signup_validity',
            'controller' => ApiSignupValidateController::class
        ]
    ]
)]
class UserSingup extends EntityCommon
{
    protected $password;

    #[\ApiPlatform\Core\Annotation\ApiProperty(identifier: true)]
    protected $username;

    protected $email;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
