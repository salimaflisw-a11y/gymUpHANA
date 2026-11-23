<?php

namespace App\Training\Entity;

use App\Training\Repository\TrainingVideoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingVideoRepository::class)]
#[ORM\Table(name: 'training_video')]
class TrainingVideo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 60)]
    private string $category;

    #[ORM\Column(name: 'target_muscle', type: 'string', length: 100, nullable: true)]
    private ?string $targetMuscle = null;

    #[ORM\Column(type: 'string', length: 30)]
    private string $difficulty = 'ALL_LEVELS';

    #[ORM\Column(name: 'duration_minutes', type: 'integer')]
    private int $durationMinutes = 5;

    #[ORM\Column(name: 'video_url', type: 'string', length: 500)]
    private string $videoUrl;

    #[ORM\Column(name: 'thumbnail_url', type: 'string', length: 500, nullable: true)]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(name: 'instructor_name', type: 'string', length: 100, nullable: true)]
    private ?string $instructorName = null;

    #[ORM\Column(name: 'views_count', type: 'integer')]
    private int $viewsCount = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $title,
        string $category,
        string $videoUrl,
        ?string $description = null,
        ?string $targetMuscle = null,
        string $difficulty = 'ALL_LEVELS',
        int $durationMinutes = 5,
        ?string $thumbnailUrl = null,
        ?string $instructorName = null
    ) {
        $this->title = $title;
        $this->category = $category;
        $this->videoUrl = $videoUrl;
        $this->description = $description;
        $this->targetMuscle = $targetMuscle;
        $this->difficulty = $difficulty;
        $this->durationMinutes = $durationMinutes;
        $this->thumbnailUrl = $thumbnailUrl;
        $this->instructorName = $instructorName;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getTargetMuscle(): ?string
    {
        return $this->targetMuscle;
    }

    public function getDifficulty(): string
    {
        return $this->difficulty;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function getVideoUrl(): string
    {
        return $this->videoUrl;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function getInstructorName(): ?string
    {
        return $this->instructorName;
    }

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function incrementViews(): void
    {
        ++$this->viewsCount;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
