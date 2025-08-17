<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\ApiUserUpdatePasswordController;
use App\Utils\EntityCommon;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/user/update/password',
            name: 'app_api_user_update_password',
            controller: ApiUserUpdatePasswordController::class,
        ),
    ],
)]
class UserPassword extends EntityCommon
{
    protected string $passwordlOld;

    protected string $passwordlNew;

    public function getPasswordOld(): string
    {
        return $this->passwordlOld;
    }

    public function setPasswordOld(string $passwordlOld): self
    {
        $this->passwordlOld = $passwordlOld;

        return $this;
    }

    public function getPasswordNew(): ?string
    {
        return $this->passwordlNew;
    }

    public function setPasswordNew(string $passwordlNew): self
    {
        $this->passwordlNew = $passwordlNew;

        return $this;
    }
}
