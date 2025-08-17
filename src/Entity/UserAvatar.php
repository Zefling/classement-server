<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\ApiUserUpdateAvatarController;
use App\Utils\EntityCommon;


#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/user/update/avatar',
            name: 'app_api_user_update_avatar',
            controller: ApiUserUpdateAvatarController::class,
        ),
    ],
)]
class UserAvatar extends EntityCommon
{
    protected string $avatar;

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }
}
