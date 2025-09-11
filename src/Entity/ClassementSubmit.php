<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use App\Controller\ApiAddClassementController;
use App\Controller\ApiAdminClassementsController;
use App\Controller\ApiAdminClassementStatusController;
use App\Controller\ApiClassementStatusController;
use App\Controller\ApiDeleteClassementController;
use App\Controller\ApiGetCategoriesHomeController;
use App\Controller\ApiGetClassementController;
use App\Controller\ApiGetClassementsController;
use App\Controller\ApiGetClassementsTemplateController;
use App\Controller\ApiGetLastClassementsController;
use App\Controller\ApiTestLinkIdController;
use App\Utils\EntityCommon;

#[ApiResource(
    operations: [
        // CollectionOperations
        new Post(
            uriTemplate: '/classement',
            name: 'app_api_classement_add',
            controller: ApiAddClassementController::class,
        ),
        new GetCollection(
            uriTemplate: '/classements',
            name: 'app_api_classements_get',
            controller: ApiGetClassementsController::class,
        ),
        new GetCollection(
            uriTemplate: '/categories/home',
            name: 'app_api_group_home_get',
            controller: ApiGetCategoriesHomeController::class,
        ),
        new GetCollection(
            uriTemplate: '/classements/last',
            name: 'app_api_classements_last',
            controller: ApiGetLastClassementsController::class,
        ),
        new Get(
            uriTemplate: '/classements/template/{id}',
            name: 'app_api_classements_template_get',
            controller: ApiGetClassementsTemplateController::class,
            requirements: ['id' => '\S+'],
        ),
        new GetCollection(
            uriTemplate: '/admin/classements',
            name: 'app_api_admin_classements',
            controller: ApiAdminClassementsController::class,
        ),
        new Post(
            uriTemplate: '/testId',
            name: 'app_api_link_id_test',
            controller: ApiTestLinkIdController::class,
        ),

        // ItemOperations
        new Get(
            uriTemplate: '/classement/{id}',
            name: 'app_api_classement_get',
            controller: ApiGetClassementController::class,
            requirements: ['id' => '\S+'],
            priority: 0
        ),
        new Delete(
            uriTemplate: '/classement/{id}',
            name: 'app_api_classement_delete',
            controller: ApiDeleteClassementController::class,
            requirements: ['id' => '\S+'],
        ),
        new Post(
            uriTemplate: '/classement/status/{id}',
            name: 'app_api_user_classement_status',
            controller: ApiClassementStatusController::class,
            requirements: ['id' => '\S+'],
        ),
        new Post(
            uriTemplate: '/admin/classement/status/{id}',
            name: 'app_api_admin_classement_status',
            controller: ApiAdminClassementStatusController::class,
            requirements: ['id' => '\S+'],
        ),
    ],
    paginationEnabled: true,
)]
class ClassementSubmit extends EntityCommon
{

    protected $name;

    protected $category;

    protected $mode;

    protected $linkId;

    protected $data = [];

    protected $templateId;

    #[\ApiPlatform\Metadata\ApiProperty(identifier: true)]
    protected $rankingId;

    protected $parentId;

    protected $localId;

    protected $banner;

    protected $dateCreate;

    protected $dateChange;

    protected $user;

    protected $userAvatar;

    protected $totalItems;

    protected $totalGroups;

    protected $hidden;

    protected $deleted;

    protected $adult;

    protected $parent;

    protected $templateTotal;

    protected $password;

    protected $history;

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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

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

    public function getLinkId(): ?string
    {
        return $this->linkId;
    }

    public function setLinkId(?string $linkId): self
    {
        $this->linkId = $linkId;

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

    public function getUserAvatar(): ?string
    {
        return $this->userAvatar;
    }

    public function setUserAvatar(string $userAvatar): self
    {
        $this->userAvatar = $userAvatar;

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

    public function getAdult(): ?bool
    {
        return $this->adult;
    }

    public function setAdult(?bool $adult): self
    {
        $this->adult = $adult;

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

    public function getTemplateTotal(): ?int
    {
        return $this->templateTotal;
    }

    public function setTemplateTotal(?int $templateTotal): self
    {
        $this->templateTotal = $templateTotal;

        return $this;
    }

    public function getHistory(): ?bool
    {
        return $this->history;
    }

    public function setHistory(?bool $history): self
    {
        $this->history = $history;

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

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }
}
