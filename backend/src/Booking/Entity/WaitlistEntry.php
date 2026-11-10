<?php

namespace App\Booking\Entity;

use App\Auth\Entity\User;
use App\Booking\Repository\WaitlistEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WaitlistEntryRepository::class)]
#[ORM\Table(name: 'waitlist_entry')]
#[ORM\Index(name: 'idx_waitlist_session_status', columns: ['class_session_id', 'status'])]
class WaitlistEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ClassSession::class)]
    #[ORM\JoinColumn(name: 'class_session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ClassSession $classSession;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $member;

    #[ORM\Column(name: 'queue_position', type: 'integer')]
    private int $queuePosition;

    #[ORM\Column(type: 'string', length: 30)]
    private string $status = 'WAITING'; // WAITING, OFFERED, CLAIMED, EXPIRED, CANCELLED

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'offered_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $offeredAt = null;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct(ClassSession $classSession, User $member, int $queuePosition)
    {
        $this->classSession = $classSession;
        $this->member = $member;
        $this->queuePosition = $queuePosition;
        $this->status = 'WAITING';
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClassSession(): ClassSession
    {
        return $this->classSession;
    }

    public function getMember(): User
    {
        return $this->member;
    }

    public function getQueuePosition(): int
    {
        return $this->queuePosition;
    }

    public function setQueuePosition(int $queuePosition): self
    {
        $this->queuePosition = $queuePosition;
        return $this;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getOfferedAt(): ?\DateTimeImmutable
    {
        return $this->offeredAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function offerSpot(int $validMinutes = 15): self
    {
        $now = new \DateTimeImmutable();
        $this->status = 'OFFERED';
        $this->offeredAt = $now;
        $this->expiresAt = $now->modify(sprintf('+%d minutes', $validMinutes));
        return $this;
    }

    public function isOfferValid(): bool
    {
        if ($this->status !== 'OFFERED' || !$this->expiresAt) {
            return false;
        }
        return $this->expiresAt > new \DateTimeImmutable();
    }
}

