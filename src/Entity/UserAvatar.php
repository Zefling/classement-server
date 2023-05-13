<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiUserUpdateAvatarController;
use App\Utils\EntityCommon;


#[ApiResource(
    collectionOperations: [
        'app_api_user_update_avatar' => [
            'method' => 'POST',
            'path' => '/user/update/avatar',
            'name' => 'app_api_user_update_avatar',
            'controller' => ApiUserUpdateAvatarController::class,
        ],
    ],
    itemOperations: []
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
