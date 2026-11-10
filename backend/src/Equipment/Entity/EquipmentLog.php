<?php

namespace App\Equipment\Entity;

use App\Auth\Entity\User;
use App\Equipment\Repository\EquipmentLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipmentLogRepository::class)]
#[ORM\Table(name: 'equipment_log')]
#[ORM\Index(name: 'idx_eq_log_member_eq', columns: ['member_id', 'equipment_id'])]
class EquipmentLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $member;

    #[ORM\ManyToOne(targetEntity: Equipment::class, inversedBy: 'logs')]
    #[ORM\JoinColumn(name: 'equipment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Equipment $equipment;

    #[ORM\Column(name: 'weight_kg', type: 'float')]
    private float $weightKg;

    #[ORM\Column(type: 'integer')]
    private int $reps;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $sets = 1;

    #[ORM\Column(name: 'seat_setting', type: 'string', length: 100, nullable: true)]
    private ?string $seatSetting = null;

    #[ORM\Column(name: 'estimated_1rm', type: 'float', nullable: true)]
    private ?float $estimated1Rm = null;

    #[ORM\Column(name: 'is_personal_record', type: 'boolean', options: ['default' => false])]
    private bool $isPersonalRecord = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'logged_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $loggedAt;

    public function __construct(
        User $member,
        Equipment $equipment,
        float $weightKg,
        int $reps,
        int $sets = 1,
        ?string $seatSetting = null,
        ?string $notes = null
    ) {
        $this->member = $member;
        $this->equipment = $equipment;
        $this->weightKg = $weightKg;
        $this->reps = $reps;
        $this->sets = $sets;
        $this->seatSetting = $seatSetting;
        $this->notes = $notes;
        $this->loggedAt = new \DateTimeImmutable();

        // Calculate estimated 1 Rep Max via Epley formula
        $this->estimated1Rm = round($weightKg * (1 + ($reps / 30.0)), 1);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMember(): User
    {
        return $this->member;
    }

    public function getEquipment(): Equipment
    {
        return $this->equipment;
    }

    public function getWeightKg(): float
    {
        return $this->weightKg;
    }

    public function getReps(): int
    {
        return $this->reps;
    }

    public function getSets(): int
    {
        return $this->sets;
    }

    public function getSeatSetting(): ?string
    {
        return $this->seatSetting;
    }

    public function getEstimated1Rm(): ?float
    {
        return $this->estimated1Rm;
    }

    public function isPersonalRecord(): bool
    {
        return $this->isPersonalRecord;
    }

    public function setPersonalRecord(bool $isPersonalRecord): self
    {
        $this->isPersonalRecord = $isPersonalRecord;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getLoggedAt(): \DateTimeImmutable
    {
        return $this->loggedAt;
    }
}

