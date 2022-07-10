<?php

namespace App\Entity;

use App\Repository\ClassementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

enum Category: string
{
    case Anime = 'anime';
    case Game = 'game';
    case VideoGame = 'video.game';
    case BoardGame = 'board.game';
    case Movie = 'movie';
    case Series = 'series';
    case Vehicle = 'vehicle';
    case Other = 'other';
}

#[
    ORM\Entity(
        repositoryClass: ClassementRepository::class
    ),
    UniqueConstraint(
        name: "index_id",
        columns: ["template_id", "ranking_id"]
    )
]
class Classement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['classement:list', 'classement:item'])]
    private $name;

    #[ORM\Column(type: 'string', enumType: Category::class, length: 20)]
    #[Groups(['classement:list', 'classement:item'])]
    private $category;

    #[ORM\Column(type: 'json')]
    #[Groups(['classement:item'])]
    private $data = [];

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['classement:list', 'classement:item'])]
    private $dateCreate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['classement:list', 'classement:item'])]
    private $dateChange;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[Groups(['classement:item'])]
    private $User;

    #[ORM\Column(type: 'string', length: 255)]
    private $templateId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $rankingId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $localId;

    #[ORM\Column(type: 'boolean')]
    private $hide;

    #[ORM\Column(type: 'boolean')]
    private $deleted;

    #[ORM\Column(type: 'string', length: 255)]
    private $banner;

    #[ORM\Column(type: 'boolean')]
    private $parent;

    #[ORM\ManyToMany(targetEntity: File::class, inversedBy: 'classements')]
    private $files;

    #[ORM\Column(type: 'integer')]
    private $totalItems;

    #[ORM\Column(type: 'integer')]
    private $totalGroups;

    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
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

    public function getLocalId(): ?string
    {
        return $this->localId;
    }

    public function setLocalId(string $localId): self
    {
        $this->localId = $localId;

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

    public function getParent(): ?bool
    {
        return $this->parent;
    }

    public function setParent(bool $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files[] = $file;
        }

        return $this;
    }

    public function removeFile(File $file): self
    {
        $this->files->removeElement($file);

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
}
