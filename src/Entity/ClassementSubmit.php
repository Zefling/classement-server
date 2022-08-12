<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiAddClassementController;
use App\Controller\ApiAdminClassementsController;
use App\Controller\ApiAdminClassementStatusController;
use App\Controller\ApiDeleteClassementController;
use App\Controller\ApiGetCategoriesHomeController;
use App\Controller\ApiGetClassementController;
use App\Controller\ApiGetClassementsController;
use App\Controller\ApiGetClassementsTemplateController;
use App\Utils\EntityCommon;

#[ApiResource(
    collectionOperations: [
        'post_publication' => [
            'method' => 'POST',
            'path' => '/classement',
            'name' => 'app_api_classement_add',
            'controller' => ApiAddClassementController::class,
        ],
        'get_publications' => [
            'method' => 'GET',
            'path' => '/classements',
            'name' => 'app_api_classements_get',
            'controller' => ApiGetClassementsController::class,
        ],
        'app_api_group_home_get' => [
            'method' => 'GET',
            'path' => '/categories/home',
            'name' => 'app_api_group_home_get',
            'controller' => ApiGetCategoriesHomeController::class,
        ],
        'app_api_classements_template_get' => [
            'method' => 'GET',
            'path' => '/classements/template/{id}',
            'requirements' => ['id' => '\s+'],
            'name' => 'app_api_classements_template_get',
            'controller' => ApiGetClassementsTemplateController::class,
        ],
        'app_api_admin_classements' => [
            'method' => 'GET',
            'path' => '/admin/classements',
            'name' => 'app_api_admin_classements',
            'controller' => ApiAdminClassementsController::class,
        ],
    ],
    itemOperations: [
        'get_publication' => [
            'method' => 'GET',
            'path' => '/classement/{id}',
            'requirements' => ['id' => '\s+'],
            'name' => 'app_api_classement_get',
            'controller' => ApiGetClassementController::class,
        ],
        'delete_publication' => [
            'method' => 'DELETE',
            'path' => '/classement/{id}',
            'requirements' => ['id' => '\s+'],
            'name' => 'app_api_classement_delete',
            'controller' => ApiDeleteClassementController::class,
        ],
        'app_api_admin_classement_status' => [
            'method' => 'POST',
            'path' => '/admin/classement/status/{id}',
            'requirements' => ['id' => '\s+'],
            'name' => 'app_api_admin_classement_status',
            'controller' => ApiAdminClassementStatusController::class,
        ],
    ],
    paginationEnabled: true,
)]
class ClassementSubmit extends EntityCommon
{

    protected $name;

    protected $category;

    protected $data = [];

    protected $templateId;

    #[\ApiPlatform\Core\Annotation\ApiProperty(identifier: true)]
    protected $rankingId;

    protected $parentId;

    protected $localId;

    protected $banner;

    protected $dateCreate;

    protected $dateChange;

    protected $user;

    protected $totalItems;

    protected $totalGroups;

    protected $hidden;

    protected $deleted;

    protected $parent;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

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

    public function getTemplateId(): ?string
    {
        return $this->templateId;
    }

    public function setTemplateId(?string $templateId): self
    {
        $this->templateId = $templateId;

        return $this;
    }

    public function getRankingId(): ?string
    {
        return $this->rankingId;
    }

    public function setRankingId(?string $rankingId): self
    {
        $this->rankingId = $rankingId;

        return $this;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(?string $parentId): self
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getLocalId(): ?string
    {
        return $this->localId;
    }

    public function setLocalId(?string $localId): self
    {
        $this->localId = $localId;

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(string $banner): self
    {
        $this->banner = $banner;

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

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTotalItems(): ?int
    {
        return $this->totalItems;
    }

    public function setTotalItems(int $totalItems): self
    {
        $this->totalItems = $totalItems;

        return $this;
    }

    public function getTotalGroups(): ?int
    {
        return $this->totalGroups;
    }

    public function setTotalGroups(int $totalGroups): self
    {
        $this->totalGroups = $totalGroups;

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

    public function getParent(): ?bool
    {
        return $this->parent;
    }

    public function setParent(?bool $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
