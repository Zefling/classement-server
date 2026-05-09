<?php

namespace App\Entity;

use App\Repository\ClassementVoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ClassementVoteRepository::class)]
#[ORM\Table(name: 'classement_vote')]
#[ORM\UniqueConstraint(name: 'unique_user_classement_vote_type', columns: ['user_id', 'classement_id', 'vote_type'])]
class ClassementVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Classement::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Classement $classement = null;

    #[ORM\Column(type: 'string', length: 10)]
    #[Groups(['vote:read'])]
    private ?string $voteType = null; // Single emoji like '👍', '🥰', '🤣'

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['vote:read'])]
    private ?\DateTimeImmutable $dateCreate = null;

    public function __construct()
    {
        $this->dateCreate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getClassement(): ?Classement
    {
        return $this->classement;
    }

    public function setClassement(?Classement $classement): self
    {
        $this->classement = $classement;
        return $this;
    }

    public function getVoteType(): ?string
    {
        return $this->voteType;
    }

    public function setVoteType(string $voteType): self
    {
        if (empty($voteType)) {
            throw new \InvalidArgumentException('Vote type must be a non-empty string emoji');
        }
        $this->voteType = $voteType;
        return $this;
    }

    public function getDateCreate(): ?\DateTimeImmutable
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeImmutable $dateCreate): self
    {
        $this->dateCreate = $dateCreate;
        return $this;
    }
}
