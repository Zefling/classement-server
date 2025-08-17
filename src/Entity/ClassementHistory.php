<?php

namespace App\Entity;

use App\Repository\ClassementHistoryRepository;
use App\Entity\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[
    ORM\Entity(repositoryClass: ClassementHistoryRepository::class),
    Index(
        name: "rankingId",
        columns: ["ranking_id"]
    )
]
class ClassementHistory
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', enumType: Category::class, length: 20)]
    private $category;

    #[ORM\Column(type: 'string', enumType: Mode::class, length: 20, options: ["default" => Mode::Default])]
    private $mode;

    #[ORM\Column(type: 'json')]
    private $data = [];

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $date;

    #[ORM\Column(type: 'string', length: 255)]
    private $rankingId;

    #[ORM\Column(type: 'boolean')]
    private $deleted;

    #[ORM\Column(type: 'string', length: 255)]
    private $banner;

    #[ORM\ManyToMany(targetEntity: File::class, inversedBy: 'classements', cascade: ["persist"])]
    private $files;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'classements', cascade: ["persist"])]
    private $tags;

    #[ORM\Column(type: 'integer')]
    private $totalItems;

    #[ORM\Column(type: 'integer')]
    private $totalGroups;


    function __construct(?Classement $classement)
    {
        $this->files = new ArrayCollection();
        $this->tags = new ArrayCollection();

        if ($classement) {
            $this->name        = $classement->getName();
            $this->category    = $classement->getCategory();
            $this->mode        = $classement->getMode();
            $this->data        = $classement->getData();
            $this->date        = $classement->getDateChange() ?? $classement->getDateCreate();
            $this->rankingId   = $classement->getRankingId();
            $this->deleted     = $classement->getDeleted();
            $this->banner      = $classement->getBanner();
            $this->totalItems  = $classement->getTotalItems();
            $this->totalGroups = $classement->getTotalGroups();
            $this->files       = $classement->getFiles();
            $this->tags        = $classement->getTags();
        }
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

    public function getMode(): ?Mode
    {
        return $this->mode;
    }

    public function setMode(Mode $mode): self
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

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
