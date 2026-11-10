<?php

namespace App\Gamification\Entity;

use App\Auth\Entity\User;
use App\Gamification\Repository\ChallengeParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChallengeParticipantRepository::class)]
#[ORM\Table(name: 'challenge_participant')]
#[ORM\UniqueConstraint(name: 'uniq_challenge_member', columns: ['challenge_id', 'member_id'])]
class ChallengeParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CommunityChallenge::class, inversedBy: 'participants')]
    #[ORM\JoinColumn(name: 'challenge_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private CommunityChallenge $challenge;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $member;

    #[ORM\Column(name: 'contributed_amount', type: 'integer', options: ['default' => 0])]
    private int $contributedAmount = 0;

    #[ORM\Column(name: 'joined_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    public function __construct(CommunityChallenge $challenge, User $member)
    {
        $this->challenge = $challenge;
        $this->member = $member;
        $this->contributedAmount = 0;
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChallenge(): CommunityChallenge
    {
        return $this->challenge;
    }

    public function getMember(): User
    {
        return $this->member;
    }

    public function getContributedAmount(): int
    {
        return $this->contributedAmount;
    }

    public function addContribution(int $amount): self
    {
        $this->contributedAmount += $amount;
        return $this;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }
}

