<?php

namespace App\Gamification\Entity;

use App\Gamification\Repository\CommunityChallengeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityChallengeRepository::class)]
#[ORM\Table(name: 'community_challenge')]
class CommunityChallenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(name: 'goal_metric', type: 'string', length: 50)]
    private string $goalMetric = 'TOTAL_VISITS'; // TOTAL_VISITS, TOTAL_MINUTES, STREAK_DAYS

    #[ORM\Column(name: 'target_goal', type: 'integer')]
    private int $targetGoal;

    #[ORM\Column(name: 'current_progress', type: 'integer', options: ['default' => 0])]
    private int $currentProgress = 0;

    #[ORM\Column(name: 'start_date', type: 'datetime_immutable')]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(name: 'end_date', type: 'datetime_immutable')]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(type: 'string', length: 30)]
    private string $status = 'ACTIVE'; // ACTIVE, COMPLETED, EXPIRED

    #[ORM\Column(name: 'reward_description', type: 'string', length: 255, nullable: true)]
    private ?string $rewardDescription = null;

    /**
     * @var Collection<int, ChallengeParticipant>
     */
    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: ChallengeParticipant::class, cascade: ['remove'])]
    private Collection $participants;

    public function __construct(
        string $title,
        string $description,
        int $targetGoal,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        string $goalMetric = 'TOTAL_VISITS',
        ?string $rewardDescription = null
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->targetGoal = $targetGoal;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->goalMetric = $goalMetric;
        $this->rewardDescription = $rewardDescription;
        $this->status = 'ACTIVE';
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getGoalMetric(): string
    {
        return $this->goalMetric;
    }

    public function getTargetGoal(): int
    {
        return $this->targetGoal;
    }

    public function getCurrentProgress(): int
    {
        return $this->currentProgress;
    }

    public function addProgress(int $amount): self
    {
        $this->currentProgress += $amount;
        if ($this->currentProgress >= $this->targetGoal) {
            $this->status = 'COMPLETED';
        }
        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
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

    public function getRewardDescription(): ?string
    {
        return $this->rewardDescription;
    }

    /**
     * @return Collection<int, ChallengeParticipant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function getProgressPercentage(): float
    {
        if ($this->targetGoal <= 0) {
            return 0.0;
        }
        return min(100.0, round(($this->currentProgress / $this->targetGoal) * 100, 1));
    }
}

