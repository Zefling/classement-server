<?php

namespace App\Entity;

use App\Repository\ClassementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

enum Category: string
{
    case Animal = "animal";
    case Anime = "anime";
    case BoardGame = "board.game";
    case Book = "book";
    case Brand = "brand";
    case Comics = "comics";
    case Computer = "computer";
    case Ecology = "ecology";
    case Entertainment = "entertainment";
    case Figure = "figure";
    case Food = "food";
    case Game = "game";
    case Geography = "geography";
    case History = "history";
    case Language = "language";
    case Manga = "manga";
    case Movie = "movie";
    case Music = "music";
    case People = "people";
    case Politics = "politics";
    case Place = "place";
    case Programming = "programming";
    case Roleplaying = "roleplaying";
    case Science = "science";
    case Series = "series";
    case Show = "show";
    case Sport = "sport";
    case Technology = "technology";
    case Vehicle = "vehicle";
    case VideoGame = "video.game";
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
class Classement implements PasswordAuthenticatedUserInterface
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

    #[ORM\Column(type: 'string', length: 255,)]
    private $templateId;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $rankingId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $parentId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $localId;

    #[ORM\Column(type: 'boolean')]
    private $hidden;

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

    #[ORM\Column(type: 'string', nullable: true)]
    protected $password;

    private $templateTotal;

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

    public function setLocalId(string $localId): self
    {
        $this->localId = $localId;

        return $this;
    }

    public function getHidden(): ?bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

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

    public function getTemplateTotal(): ?int
    {
        return $this->templateTotal;
    }

    public function setTemplateTotal(?int $templateTotal): self
    {
        $this->templateTotal = $templateTotal;

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
