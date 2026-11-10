<?php

namespace App\Social\Entity;

use App\Auth\Entity\User;
use App\Social\Repository\BuddyProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BuddyProfileRepository::class)]
#[ORM\Table(name: 'buddy_profile')]
class BuddyProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $member;

    #[ORM\Column(name: 'training_style', type: 'string', length: 50)]
    private string $trainingStyle = 'GENERAL_FITNESS';

    #[ORM\Column(name: 'fitness_level', type: 'string', length: 30)]
    private string $fitnessLevel = 'INTERMEDIATE'; // BEGINNER, INTERMEDIATE, ADVANCED

    #[ORM\Column(name: 'preferred_days', type: 'json')]
    private array $preferredDays = [];

    #[ORM\Column(name: 'preferred_time', type: 'string', length: 30)]
    private string $preferredTime = 'EVENING'; // MORNING, AFTERNOON, EVENING

    #[ORM\Column(name: 'looking_for_spotter', type: 'boolean', options: ['default' => true])]
    private bool $lookingForSpotter = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(
        User $member,
        string $trainingStyle = 'GENERAL_FITNESS',
        string $fitnessLevel = 'INTERMEDIATE',
        array $preferredDays = ['MONDAY', 'WEDNESDAY', 'FRIDAY'],
        string $preferredTime = 'EVENING',
        bool $lookingForSpotter = true,
        ?string $bio = null
    ) {
        $this->member = $member;
        $this->trainingStyle = $trainingStyle;
        $this->fitnessLevel = $fitnessLevel;
        $this->preferredDays = $preferredDays;
        $this->preferredTime = $preferredTime;
        $this->lookingForSpotter = $lookingForSpotter;
        $this->bio = $bio;
        $this->isActive = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMember(): User
    {
        return $this->member;
    }

    public function getTrainingStyle(): string
    {
        return $this->trainingStyle;
    }

    public function setTrainingStyle(string $trainingStyle): self
    {
        $this->trainingStyle = $trainingStyle;
        return $this;
    }

    public function getFitnessLevel(): string
    {
        return $this->fitnessLevel;
    }

    public function setFitnessLevel(string $fitnessLevel): self
    {
        $this->fitnessLevel = $fitnessLevel;
        return $this;
    }

    public function getPreferredDays(): array
    {
        return $this->preferredDays;
    }

    public function setPreferredDays(array $preferredDays): self
    {
        $this->preferredDays = $preferredDays;
        return $this;
    }

    public function getPreferredTime(): string
    {
        return $this->preferredTime;
    }

    public function setPreferredTime(string $preferredTime): self
    {
        $this->preferredTime = $preferredTime;
        return $this;
    }

    public function isLookingForSpotter(): bool
    {
        return $this->lookingForSpotter;
    }

    public function setLookingForSpotter(bool $lookingForSpotter): self
    {
        $this->lookingForSpotter = $lookingForSpotter;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }
}

