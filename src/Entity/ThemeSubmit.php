<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiAddThemeController;
use App\Controller\ApiDeleteThemeController;
use App\Controller\ApiGetThemeController;
use App\Controller\ApiGetThemesController;
use App\Utils\EntityCommon;

#[ApiResource(
    collectionOperations: [
        'post_publication' => [
            'method' => 'POST',
            'path' => '/theme',
            'name' => 'app_api_theme_add',
            'controller' => ApiAddThemeController::class,
        ],
        'get_publications' => [
            'method' => 'GET',
            'path' => '/themes',
            'name' => 'app_api_themes_get',
            'controller' => ApiGetThemesController::class,
        ],
    ],
    itemOperations: [
        'get_publication' => [
            'method' => 'GET',
            'path' => '/theme/{id}',
            'requirements' => ['id' => '\s+'],
            'name' => 'app_api_theme_get',
            'controller' => ApiGetThemeController::class,
        ],
        'delete_publication' => [
            'method' => 'DELETE',
            'path' => '/theme/{id}',
            'requirements' => ['id' => '\s+'],
            'name' => 'app_api_theme_delete',
            'controller' => ApiDeleteThemeController::class,
        ],
    ],
    paginationEnabled: true,
)]
class ThemeSubmit extends EntityCommon
{

    protected $name;

    protected $mode;

    protected $data = [];

    protected $dateCreate;

    protected $dateChange;

    #[\ApiPlatform\Core\Annotation\ApiProperty(identifier: true)]
    protected $themeId;

    protected $user;

    protected $hidden;

    protected $deleted;

    protected $withHistory;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getThemeId(): ?string
    {
        return $this->themeId;
    }

    public function setThemeId(?string $themeId): self
    {
        $this->themeId = $themeId;

        return $this;
    }

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): self
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    public function getDateChange(): ?\DateTimeInterface
    {
        return $this->dateChange;
    }

    public function setDateChange(?\DateTimeInterface $dateChange): self
    {
        $this->dateChange = $dateChange;

        return $this;
    }

    public function getHidden(): ?bool
    {
        return $this->hidden;
    }

    public function setHidden(?bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getWithHistory(): ?int
    {
        return $this->withHistory;
    }

    public function setWithHistory(?int $withHistory): self
    {
        $this->withHistory = $withHistory;

        return $this;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }
}
