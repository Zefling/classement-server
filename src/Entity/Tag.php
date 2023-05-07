<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiGetTagsController;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    collectionOperations: [],
    itemOperations: [
        'app_api_tags_search' => [
            'method' => 'GET',
            'path' => '/tags/{tag}',
            'requirements' => ['id' => '\s+'],
            'name' => 'app_api_tags_search',
            'controller' => ApiGetTagsController::class,
        ],
    ]
)]
#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $label = null;

    #[ORM\ManyToMany(targetEntity: Classement::class, mappedBy: 'tags')]
    private $classements;

    #[ORM\ManyToMany(targetEntity: ClassementHistory::class, mappedBy: 'tags')]
    private $classementHistorys;

    public function __construct()
    {
        $this->classements = new ArrayCollection();
        $this->classementHistorys = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection<int, Classement>
     */
    public function getClassements(): Collection
    {
        return $this->classements;
    }

    public function addClassement(Classement $classement): self
    {
        if (!$this->classements->contains($classement)) {
            $this->classements->add($classement);
        }

        return $this;
    }

    public function removeClassement(Classement $classement): self
    {
        $this->classements->removeElement($classement);

        return $this;
    }

    /**
     * @return Collection<int, ClassementHistory>
     */
    public function getClassementHistorys(): Collection
    {
        return $this->classementHistorys;
    }

    public function addClassementHistory(ClassementHistory $classementHistory): self
    {
        if (!$this->classementHistorys->contains($classementHistory)) {
            $this->classementHistorys->add($classementHistory);
        }

        return $this;
    }

    public function removeClassementHistory(ClassementHistory $classementHistory): self
    {
        $this->classementHistorys->removeElement($classementHistory);

        return $this;
    }
}
