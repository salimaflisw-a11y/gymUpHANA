<?php

namespace App\Tests\Unit\Attendance;

use App\Attendance\DTO\CheckInResult;
use App\Attendance\Entity\Schedule;
use App\Attendance\Entity\Streak;
use App\Attendance\Repository\CheckInRepository;
use App\Attendance\Repository\ScheduleRepository;
use App\Attendance\Repository\StreakRepository;
use App\Attendance\Service\StreakService;
use App\Auth\Entity\User;
use App\Membership\Entity\Plan;
use App\Membership\Entity\Subscription;
use App\Membership\Repository\SubscriptionRepository;
use App\Shared\Enum\PlanAccessScope;
use App\Shared\Enum\SubscriptionStatus;
use App\Shared\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StreakServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject  $em;
    private CheckInRepository&MockObject       $checkInRepo;
    private StreakRepository&MockObject         $streakRepo;
    private ScheduleRepository&MockObject      $scheduleRepo;
    private SubscriptionRepository&MockObject  $subRepo;
    private StreakService                      $service;

    protected function setUp(): void
    {
        $this->em           = $this->createMock(EntityManagerInterface::class);
        $this->checkInRepo  = $this->createMock(CheckInRepository::class);
        $this->streakRepo   = $this->createMock(StreakRepository::class);
        $this->scheduleRepo = $this->createMock(ScheduleRepository::class);
        $this->subRepo      = $this->createMock(SubscriptionRepository::class);

        $this->service = new StreakService(
            $this->em,
            $this->checkInRepo,
            $this->streakRepo,
            $this->scheduleRepo,
            $this->subRepo,
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        $u = new User();
        $u->setEmail('m@gym.com')->setPassword('x')->setRole(UserRole::MEMBER)->setStatus('ACTIVE');
        return $u;
    }

    private function makeActiveSubscription(User $member, PlanAccessScope $scope = PlanAccessScope::BOTH): Subscription
    {
        $plan = new Plan();
        $plan->setName('Open')->setPrice(30)->setDurationDays(30)
             ->setAccessScope($scope)->setIsActive(true);
        $sub = new Subscription();
        $sub->setMember($member)->setPlan($plan)->setStatus(SubscriptionStatus::ACTIVE);
        $sub->setStartDate(new \DateTimeImmutable('today'));
        $sub->setEndDate(new \DateTimeImmutable('+30 days'));
        return $sub;
    }

    private function makeSchedule(array $days): Schedule
    {
        $s = new Schedule();
        $s->setWeekDays($days);
        return $s;
    }

    // ── processCheckIn ────────────────────────────────────────────────────────

    public function testCheckInThrowsWhenNoActiveSubscription(): void
    {
        $member = $this->makeUser();
        $this->subRepo->method('findActiveSubscription')->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Rule 8');
        $this->service->processCheckIn($member);
    }

    public function testCheckInThrowsWhenPlanDoesNotAllowOpenGym(): void
    {
        $member = $this->makeUser();
        $sub    = $this->makeActiveSubscription($member, PlanAccessScope::CLASSES);
        $this->subRepo->method('findActiveSubscription')->willReturn($sub);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Rule 8');
        $this->service->processCheckIn($member);
    }

    public function testCheckInRecordsWithoutStreakWhenNoScheduleSet(): void
    {
        $member = $this->makeUser();
        $sub    = $this->makeActiveSubscription($member);
        $streak = new Streak($member);

        $this->subRepo->method('findActiveSubscription')->willReturn($sub);
        $this->streakRepo->method('findByMember')->willReturn($streak);
        $this->scheduleRepo->method('findByMember')->willReturn(null);
        $this->checkInRepo->method('findTodayCheckIn')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');
        $this->streakRepo->method('save');

        $result = $this->service->processCheckIn($member);

        $this->assertTrue($result->success);
        $this->assertFalse($result->streakIncremented);
        $this->assertStringContainsString('schedule', strtolower($result->message));
    }

    public function testFirstCheckInOnScheduledDaySetsStreakToOne(): void
    {
        $member = $this->makeUser();
        $sub    = $this->makeActiveSubscription($member);
        $streak = new Streak($member);

        // Scan on a known Monday
        $monday = new \DateTimeImmutable('2024-01-08 10:00:00'); // This is a Monday
        $schedule = $this->makeSchedule(['MONDAY', 'WEDNESDAY', 'FRIDAY']);

        $this->subRepo->method('findActiveSubscription')->willReturn($sub);
        $this->streakRepo->method('findByMember')->willReturn($streak);
        $this->scheduleRepo->method('findByMember')->willReturn($schedule);
        $this->checkInRepo->method('findTodayCheckIn')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');
        $this->streakRepo->method('save');

        $result = $this->service->processCheckIn($member, $monday);

        $this->assertTrue($result->streakIncremented);
        $this->assertSame(1, $result->currentStreak);
    }

    public function testCheckInOnNonScheduledDayDoesNotIncrementStreak(): void
    {
        $member = $this->makeUser();
        $sub    = $this->makeActiveSubscription($member);
        $streak = new Streak($member);

        // Scan on a Sunday, schedule only has weekdays
        $sunday = new \DateTimeImmutable('2024-01-14 10:00:00'); // Sunday
        $schedule = $this->makeSchedule(['MONDAY', 'WEDNESDAY', 'FRIDAY']);

        $this->subRepo->method('findActiveSubscription')->willReturn($sub);
        $this->streakRepo->method('findByMember')->willReturn($streak);
        $this->scheduleRepo->method('findByMember')->willReturn($schedule);
        $this->checkInRepo->method('findTodayCheckIn')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');
        $this->streakRepo->method('save');

        $result = $this->service->processCheckIn($member, $sunday);

        $this->assertFalse($result->streakIncremented);
        $this->assertSame(0, $result->currentStreak);
    }

    public function testConsecutiveScheduledCheckInsIncrementsStreak(): void
    {
        $member = $this->makeUser();
        $sub    = $this->makeActiveSubscription($member);

        // Streak already at 1 from Monday
        $streak = new Streak($member);
        $streak->setCurrentStreak(1);
        $lastMonday = new \DateTimeImmutable('2024-01-08');
        $streak->setLastSuccessDate($lastMonday);

        $wednesday = new \DateTimeImmutable('2024-01-10 10:00:00'); // Wednesday
        $schedule  = $this->makeSchedule(['MONDAY', 'WEDNESDAY']);

        $this->subRepo->method('findActiveSubscription')->willReturn($sub);
        $this->streakRepo->method('findByMember')->willReturn($streak);
        $this->scheduleRepo->method('findByMember')->willReturn($schedule);
        // No missed days between Monday and Wednesday (Tuesday is not scheduled)
        $this->checkInRepo->method('findTodayCheckIn')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');
        $this->streakRepo->method('save');

        $result = $this->service->processCheckIn($member, $wednesday);

        $this->assertTrue($result->streakIncremented);
        $this->assertSame(2, $result->currentStreak);
    }

    // ── hasMissedScheduledDays ────────────────────────────────────────────────

    public function testHasMissedScheduledDaysReturnsFalseWhenConsecutive(): void
    {
        $member   = $this->makeUser();
        $schedule = $this->makeSchedule(['MONDAY', 'WEDNESDAY']);

        // Monday to Wednesday — only Tuesday is between them, and it's not scheduled
        $monday    = new \DateTimeImmutable('2024-01-08');
        $wednesday = new \DateTimeImmutable('2024-01-10');

        // No check-in queries needed since Tuesday is not in the schedule
        $result = $this->service->hasMissedScheduledDays($member, $schedule, $monday, $wednesday);

        $this->assertFalse($result);
    }

    public function testHasMissedScheduledDaysReturnsTrueWhenDayMissed(): void
    {
        $member   = $this->makeUser();
        $schedule = $this->makeSchedule(['MONDAY', 'TUESDAY', 'WEDNESDAY']);

        // Monday to Wednesday — Tuesday IS scheduled and was not checked in
        $monday    = new \DateTimeImmutable('2024-01-08');
        $wednesday = new \DateTimeImmutable('2024-01-10');

        $this->checkInRepo
            ->method('findTodayCheckIn')
            ->willReturn(null); // no check-in on Tuesday

        $result = $this->service->hasMissedScheduledDays($member, $schedule, $monday, $wednesday);

        $this->assertTrue($result);
    }

    public function testHasMissedScheduledDaysReturnsFalseForSameDay(): void
    {
        $member   = $this->makeUser();
        $schedule = $this->makeSchedule(['MONDAY']);
        $today    = new \DateTimeImmutable('2024-01-08');

        $result = $this->service->hasMissedScheduledDays($member, $schedule, $today, $today);

        $this->assertFalse($result);
    }

    // ── getEffectiveStreak ────────────────────────────────────────────────────

    public function testGetEffectiveStreakReturnsZeroWhenNoStreak(): void
    {
        $member = $this->makeUser();
        $this->streakRepo->method('findByMember')->willReturn(null);

        $this->assertSame(0, $this->service->getEffectiveStreak($member));
    }
}
