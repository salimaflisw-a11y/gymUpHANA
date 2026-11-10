<?php

namespace App\Gamification\Entity;

use App\Auth\Entity\User;
use App\Gamification\Repository\MemberBadgeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MemberBadgeRepository::class)]
#[ORM\Table(name: 'member_badge')]
#[ORM\UniqueConstraint(name: 'uniq_member_badge', columns: ['member_id', 'badge_id'])]
class MemberBadge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $member;

    #[ORM\ManyToOne(targetEntity: Badge::class, inversedBy: 'memberBadges')]
    #[ORM\JoinColumn(name: 'badge_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Badge $badge;

    #[ORM\Column(name: 'unlocked_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $unlockedAt;

    #[ORM\Column(name: 'is_perk_claimed', type: 'boolean', options: ['default' => false])]
    private bool $isPerkClaimed = false;

    #[ORM\Column(name: 'claimed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $claimedAt = null;

    public function __construct(User $member, Badge $badge)
    {
        $this->member = $member;
        $this->badge = $badge;
        $this->unlockedAt = new \DateTimeImmutable();
        $this->isPerkClaimed = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMember(): User
    {
        return $this->member;
    }

    public function getBadge(): Badge
    {
        return $this->badge;
    }

    public function getUnlockedAt(): \DateTimeImmutable
    {
        return $this->unlockedAt;
    }

    public function isPerkClaimed(): bool
    {
        return $this->isPerkClaimed;
    }

    public function claimPerk(): self
    {
        $this->isPerkClaimed = true;
        $this->claimedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getClaimedAt(): ?\DateTimeImmutable
    {
        return $this->claimedAt;
    }
}

