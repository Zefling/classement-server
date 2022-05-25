<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Controller\ApiClassementController;
use App\Repository\ClassementRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ClassementRepository::class)]
#[ApiResource(
    collectionOperations: [
        'get' => ['normalization_context' => ['groups' => 'classement:list']],
        'post_publication' => [
            'method' => 'POST',
            'path' => '/classement/add',
            'controller' => ApiClassementController::class,
        ],
    ],
    itemOperations: [
        'get' => ['normalization_context' => ['groups' => 'classement:item']],

    ],
    order: ['username' => 'ASC'],
    paginationEnabled: false,
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'name' => 'exact'])]
class Classement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['classement:list', 'classement:item'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['classement:list', 'classement:item'])]
    private $name;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['classement:list', 'classement:item'])]
    private $groupName;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['classement:list', 'classement:item'])]
    private $picutre;

    #[ORM\Column(type: 'json')]
    #[Groups(['classement:item'])]
    private $data = [];

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups(['classement:list', 'classement:item'])]
    private $dateCreate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['classement:list', 'classement:item'])]
    private $dateChange;

    #[ORM\OneToOne(targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[Groups(['classement:item'])]
    private $User;

    #[ORM\Column(type: 'string', length: 255)]
    private $templateId;

    #[ORM\Column(type: 'string', length: 255)]
    private $rankingId;

    #[ORM\Column(type: 'boolean')]
    private $hide;

    #[ORM\Column(type: 'boolean')]
    private $deleted;

    #[ORM\Column(type: 'string', length: 255)]
    private $banner;

    #[ORM\Column(type: 'string', length: 255)]
    private $parentId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function setGroupName(string $groupName): self
    {
        $this->groupName = $groupName;

        return $this;
    }

    public function getPicutre(): ?string
    {
        return $this->picutre;
    }

    public function setPicutre(string $picutre): self
    {
        $this->picutre = $picutre;

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

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): self
    {
        $this->User = $User;

        return $this;
    }

    public function getTemplateId(): ?string
    {
        return $this->templateId;
    }

    public function setTemplateId(string $templateId): self
    {
        $this->templateId = $templateId;

        return $this;
    }

    public function getRankingId(): ?string
    {
        return $this->rankingId;
    }

    public function setRankingId(string $rankingId): self
    {
        $this->rankingId = $rankingId;

        return $this;
    }

    public function getHide(): ?bool
    {
        return $this->hide;
    }

    public function setHide(bool $hide): self
    {
        $this->hide = $hide;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

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

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(string $parentId): self
    {
        $this->parentId = $parentId;

        return $this;
    }
}
