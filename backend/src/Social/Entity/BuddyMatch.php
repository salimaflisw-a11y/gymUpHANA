<?php

namespace App\Social\Entity;

use App\Auth\Entity\User;
use App\Social\Repository\BuddyMatchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BuddyMatchRepository::class)]
#[ORM\Table(name: 'buddy_match')]
#[ORM\UniqueConstraint(name: 'uniq_requester_target', columns: ['requester_id', 'target_member_id'])]
class BuddyMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'requester_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $requester;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'target_member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $targetMember;

    #[ORM\Column(type: 'string', length: 30)]
    private string $status = 'PENDING'; // PENDING, ACCEPTED, DECLINED

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $introMessage = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $requester, User $targetMember, ?string $introMessage = null)
    {
        $this->requester = $requester;
        $this->targetMember = $targetMember;
        $this->introMessage = $introMessage;
        $this->status = 'PENDING';
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequester(): User
    {
        return $this->requester;
    }

    public function getTargetMember(): User
    {
        return $this->targetMember;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getIntroMessage(): ?string
    {
        return $this->introMessage;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

