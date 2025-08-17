<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use App\Controller\ApiSignupController;
use App\Controller\ApiSignupValidateController;
use App\Utils\EntityCommon;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/{_locale}/signup',
            name: 'app_api_signup',
            controller: ApiSignupController::class,
            requirements: ['_locale' => '%app.supported_locales%'],
        ),
        new Get(
            uriTemplate: '/signup/validity/{token}',
            name: 'app_api_signup_validity',
            controller: ApiSignupValidateController::class,
            requirements: ['token' => '\s+'],
        ),
    ],
)]
class UserSingup extends EntityCommon
{
    protected $password;

    #[\ApiPlatform\Metadata\ApiProperty(identifier: true)]
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
