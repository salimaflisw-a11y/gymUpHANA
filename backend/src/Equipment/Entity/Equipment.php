<?php

namespace App\Equipment\Entity;

use App\Equipment\Repository\EquipmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
#[ORM\Table(name: 'equipment')]
class Equipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'qr_code', type: 'string', length: 60, unique: true)]
    private string $qrCode;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'string', length: 60)]
    private string $category; // CHEST, BACK, LEGS, SHOULDERS, ARMS, CORE, CARDIO

    #[ORM\Column(name: 'target_muscles', type: 'json')]
    private array $targetMuscles = [];

    #[ORM\Column(name: 'model_number', type: 'string', length: 80, nullable: true)]
    private ?string $modelNumber = null;

    #[ORM\Column(name: 'setup_instructions', type: 'text', nullable: true)]
    private ?string $setupInstructions = null;

    #[ORM\Column(name: 'video_tutorial_url', type: 'string', length: 255, nullable: true)]
    private ?string $videoTutorialUrl = null;

    #[ORM\Column(name: 'image_url', type: 'string', length: 255, nullable: true)]
    private ?string $imageUrl = null;

    /**
     * @var Collection<int, EquipmentLog>
     */
    #[ORM\OneToMany(mappedBy: 'equipment', targetEntity: EquipmentLog::class, cascade: ['remove'])]
    private Collection $logs;

    public function __construct(
        string $qrCode,
        string $name,
        string $category,
        array $targetMuscles = [],
        ?string $setupInstructions = null,
        ?string $videoTutorialUrl = null,
        ?string $imageUrl = null,
        ?string $modelNumber = null
    ) {
        $this->qrCode = $qrCode;
        $this->name = $name;
        $this->category = $category;
        $this->targetMuscles = $targetMuscles;
        $this->setupInstructions = $setupInstructions;
        $this->videoTutorialUrl = $videoTutorialUrl;
        $this->imageUrl = $imageUrl;
        $this->modelNumber = $modelNumber;
        $this->logs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQrCode(): string
    {
        return $this->qrCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getTargetMuscles(): array
    {
        return $this->targetMuscles;
    }

    public function getModelNumber(): ?string
    {
        return $this->modelNumber;
    }

    public function getSetupInstructions(): ?string
    {
        return $this->setupInstructions;
    }

    public function getVideoTutorialUrl(): ?string
    {
        return $this->videoTutorialUrl;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @return Collection<int, EquipmentLog>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }
}

