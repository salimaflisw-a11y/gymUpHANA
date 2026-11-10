<?php

namespace App\Social\Entity;

use App\Auth\Entity\User;
use App\Social\Repository\FormReviewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormReviewRepository::class)]
#[ORM\Table(name: 'form_review')]
class FormReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $member;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'coach_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $coach = null;

    #[ORM\Column(name: 'exercise_name', type: 'string', length: 120)]
    private string $exerciseName;

    #[ORM\Column(name: 'video_url', type: 'string', length: 255)]
    private string $videoUrl;

    #[ORM\Column(name: 'member_notes', type: 'text', nullable: true)]
    private ?string $memberNotes = null;

    #[ORM\Column(name: 'coach_feedback', type: 'text', nullable: true)]
    private ?string $coachFeedback = null;

    #[ORM\Column(name: 'form_rating', type: 'integer', nullable: true)]
    private ?int $formRating = null; // 1 to 5 scale

    #[ORM\Column(type: 'string', length: 30)]
    private string $status = 'SUBMITTED'; // SUBMITTED, IN_REVIEW, COMPLETED

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'reviewed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    public function __construct(User $member, string $exerciseName, string $videoUrl, ?string $memberNotes = null)
    {
        $this->member = $member;
        $this->exerciseName = $exerciseName;
        $this->videoUrl = $videoUrl;
        $this->memberNotes = $memberNotes;
        $this->status = 'SUBMITTED';
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMember(): User
    {
        return $this->member;
    }

    public function getCoach(): ?User
    {
        return $this->coach;
    }

    public function setCoach(?User $coach): self
    {
        $this->coach = $coach;
        return $this;
    }

    public function getExerciseName(): string
    {
        return $this->exerciseName;
    }

    public function getVideoUrl(): string
    {
        return $this->videoUrl;
    }

    public function getMemberNotes(): ?string
    {
        return $this->memberNotes;
    }

    public function getCoachFeedback(): ?string
    {
        return $this->coachFeedback;
    }

    public function getFormRating(): ?int
    {
        return $this->formRating;
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

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function completeReview(User $coach, string $feedback, int $rating): self
    {
        $this->coach = $coach;
        $this->coachFeedback = $feedback;
        $this->formRating = max(1, min(5, $rating));
        $this->status = 'COMPLETED';
        $this->reviewedAt = new \DateTimeImmutable();
        return $this;
    }
}

