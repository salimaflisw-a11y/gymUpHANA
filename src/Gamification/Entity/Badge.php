<?php

namespace App\Gamification\Entity;

use App\Gamification\Repository\BadgeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BadgeRepository::class)]
#[ORM\Table(name: 'badge')]
class Badge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $code;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 50)]
    private string $icon;

    #[ORM\Column(type: 'string', length: 50)]
    private string $category = 'STREAK'; // STREAK, ATTENDANCE, CHALLENGE, SPECIAL

    #[ORM\Column(name: 'required_streak', type: 'integer', options: ['default' => 0])]
    private int $requiredStreak = 0;

    #[ORM\Column(name: 'required_visits', type: 'integer', options: ['default' => 0])]
    private int $requiredVisits = 0;

    #[ORM\Column(name: 'perk_reward', type: 'string', length: 150, nullable: true)]
    private ?string $perkReward = null;

    /**
     * @var Collection<int, MemberBadge>
     */
    #[ORM\OneToMany(mappedBy: 'badge', targetEntity: MemberBadge::class, cascade: ['remove'])]
    private Collection $memberBadges;

    public function __construct(
        string $code,
        string $name,
        string $description,
        string $icon,
        string $category = 'STREAK',
        int $requiredStreak = 0,
        int $requiredVisits = 0,
        ?string $perkReward = null
    ) {
        $this->code = $code;
        $this->name = $name;
        $this->description = $description;
        $this->icon = $icon;
        $this->category = $category;
        $this->requiredStreak = $requiredStreak;
        $this->requiredVisits = $requiredVisits;
        $this->perkReward = $perkReward;
        $this->memberBadges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getRequiredStreak(): int
    {
        return $this->requiredStreak;
    }

    public function getRequiredVisits(): int
    {
        return $this->requiredVisits;
    }

    public function getPerkReward(): ?string
    {
        return $this->perkReward;
    }

    /**
     * @return Collection<int, MemberBadge>
     */
    public function getMemberBadges(): Collection
    {
        return $this->memberBadges;
    }
}

