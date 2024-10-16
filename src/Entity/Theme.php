<?php

namespace App\Entity;

use App\Repository\ThemeRepository;
use App\Utils\WithTags;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity(
        repositoryClass: ThemeRepository::class
    ),
    UniqueConstraint(
        name: "index_id",
        columns: ["theme_id"]
    )
]
class Theme implements WithTags
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', enumType: Mode::class, length: 20, options: ["default" => Mode::Default])]
    private $mode;

    #[ORM\Column(type: 'json')]
    private $data = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private $dateCreate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateChange;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist', 'remove'])]
    private $User;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $themeId;

    #[ORM\ManyToMany(targetEntity: File::class, inversedBy: 'themes', cascade: ["persist"])]
    private $files;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'themes', cascade: ["persist"])]
    private $tags;

    #[ORM\Column(type: 'boolean')]
    private $hidden;

    #[ORM\Column(type: 'boolean')]
    private $deleted;

    public function __construct()
    {
        $this->files = new ArrayCollection();
        $this->tags = new ArrayCollection();
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

    public function getThemeId(): ?string
    {
        return $this->themeId;
    }

    public function setThemeId(?string $themeId): self
    {
        $this->themeId = $themeId;

        return $this;
    }
}
