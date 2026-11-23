<?php

namespace App\Tests\Unit\Hiring;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Hiring\Entity\Application;
use App\Hiring\Entity\JobPosting;
use App\Hiring\Repository\ApplicationRepository;
use App\Hiring\Repository\JobPostingRepository;
use App\Hiring\Service\HiringService;
use App\Hiring\Service\NotificationService;
use App\Shared\Enum\ApplicationStatus;
use App\Shared\Enum\JobPostingStatus;
use App\Shared\Enum\PositionType;
use App\Shared\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HiringServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject      $em;
    private JobPostingRepository&MockObject        $postingRepo;
    private ApplicationRepository&MockObject       $appRepo;
    private UserRepository&MockObject              $userRepo;
    private UserPasswordHasherInterface&MockObject $hasher;
    private NotificationService&MockObject         $notifier;
    private HiringService                          $service;

    protected function setUp(): void
    {
        $this->em          = $this->createMock(EntityManagerInterface::class);
        $this->postingRepo = $this->createMock(JobPostingRepository::class);
        $this->appRepo     = $this->createMock(ApplicationRepository::class);
        $this->userRepo    = $this->createMock(UserRepository::class);
        $this->hasher      = $this->createMock(UserPasswordHasherInterface::class);
        $this->notifier    = $this->createMock(NotificationService::class);

        $this->service = new HiringService(
            $this->em,
            $this->postingRepo,
            $this->appRepo,
            $this->userRepo,
            $this->hasher,
            $this->notifier,
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeOpenPosting(PositionType $type = PositionType::COACH): JobPosting
    {
        return new JobPosting($type);
    }

    private function makeApplication(JobPosting $posting): Application
    {
        return new Application($posting, 'Jane Doe', 'jane@example.com', '/uploads/cv.pdf');
    }

    // ── createJobPosting ──────────────────────────────────────────────────────

    public function testCreateJobPostingReturnsOpenPosting(): void
    {
        $this->postingRepo->expects($this->once())->method('save');

        $posting = $this->service->createJobPosting(PositionType::COACH);

        $this->assertSame(PositionType::COACH, $posting->getPositionType());
        $this->assertTrue($posting->isOpen());
    }

    // ── closeJobPosting ───────────────────────────────────────────────────────

    public function testCloseJobPostingChangesStatusToClosed(): void
    {
        $posting = $this->makeOpenPosting();
        $this->postingRepo->expects($this->once())->method('save')->with($posting);

        $this->service->closeJobPosting($posting);

        $this->assertSame(JobPostingStatus::CLOSED, $posting->getStatus());
    }

    // ── submitApplication ─────────────────────────────────────────────────────

    public function testSubmitApplicationCreatesApplication(): void
    {
        $posting = $this->makeOpenPosting();
        $this->appRepo->expects($this->once())->method('save');
        $this->notifier->expects($this->once())->method('sendApplicationReceivedEmail');

        $app = $this->service->submitApplication($posting, 'Jane Doe', 'jane@example.com', '/cv.pdf');

        $this->assertSame('Jane Doe', $app->getFullName());
        $this->assertSame('jane@example.com', $app->getEmail());
        $this->assertSame(ApplicationStatus::PENDING, $app->getStatus());
    }

    public function testSubmitApplicationTrimsAndLowercasesEmail(): void
    {
        $posting = $this->makeOpenPosting();
        $this->appRepo->method('save');
        $this->notifier->method('sendApplicationReceivedEmail');

        $app = $this->service->submitApplication($posting, 'Jane', '  JANE@EXAMPLE.COM  ', '/cv.pdf');

        $this->assertSame('jane@example.com', $app->getEmail());
    }

    public function testSubmitApplicationThrowsForClosedPosting(): void
    {
        $posting = $this->makeOpenPosting();
        $posting->setStatus(JobPostingStatus::CLOSED);

        $this->expectException(\LogicException::class);
        $this->service->submitApplication($posting, 'Jane', 'jane@example.com', '/cv.pdf');
    }

    // ── rejectApplication ─────────────────────────────────────────────────────

    public function testRejectApplicationSetsRejectedStatus(): void
    {
        $posting = $this->makeOpenPosting();
        $app     = $this->makeApplication($posting);

        $this->appRepo->expects($this->once())->method('save');
        $this->notifier->expects($this->once())->method('sendRejectionEmail');

        $this->service->rejectApplication($app);

        $this->assertSame(ApplicationStatus::REJECTED, $app->getStatus());
    }

    public function testRejectApplicationThrowsIfAlreadyProcessed(): void
    {
        $posting = $this->makeOpenPosting();
        $app     = $this->makeApplication($posting);
        $app->setStatus(ApplicationStatus::ACCEPTED);

        $this->expectException(\LogicException::class);
        $this->service->rejectApplication($app);
    }

    // ── acceptApplication ─────────────────────────────────────────────────────

    public function testAcceptApplicationCreatesNewUser(): void
    {
        $posting = $this->makeOpenPosting(PositionType::COACH);
        $app     = $this->makeApplication($posting);

        $this->userRepo->method('findOneBy')->willReturn(null);
        $this->hasher->method('hashPassword')->willReturn('hashed');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->notifier->expects($this->once())->method('sendAcceptanceCredentialsEmail');

        $user = $this->service->acceptApplication($app);

        $this->assertSame(UserRole::COACH, $user->getRole());
        $this->assertSame('jane@example.com', $user->getEmail());
        $this->assertSame(ApplicationStatus::ACCEPTED, $app->getStatus());
    }

    public function testAcceptApplicationUpgradesExistingUser(): void
    {
        $posting  = $this->makeOpenPosting(PositionType::RECEPTIONIST);
        $app      = $this->makeApplication($posting);
        $existing = new User();
        $existing->setEmail('jane@example.com')->setPassword('x')
                 ->setRole(UserRole::MEMBER)->setStatus('ACTIVE');

        $this->userRepo->method('findOneBy')->willReturn($existing);
        $this->em->method('flush');
        $this->notifier->method('sendAcceptanceCredentialsEmail');

        $user = $this->service->acceptApplication($app);

        $this->assertSame($existing, $user);
        $this->assertSame(UserRole::RECEPTIONIST, $user->getRole());
    }

    public function testAcceptApplicationThrowsIfAlreadyProcessed(): void
    {
        $posting = $this->makeOpenPosting();
        $app     = $this->makeApplication($posting);
        $app->setStatus(ApplicationStatus::REJECTED);

        $this->expectException(\LogicException::class);
        $this->service->acceptApplication($app);
    }
}
