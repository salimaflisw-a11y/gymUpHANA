<?php

namespace App\Tests\Unit\Booking;

use App\Auth\Entity\User;
use App\Booking\Entity\Booking;
use App\Booking\Entity\ClassSession;
use App\Booking\Repository\BookingRepository;
use App\Booking\Repository\ClassSessionRepository;
use App\Booking\Service\BookingService;
use App\Booking\Service\ConflictCheckerService;
use App\Membership\Entity\Plan;
use App\Membership\Entity\Subscription;
use App\Membership\Repository\SubscriptionRepository;
use App\Shared\Enum\BookingStatus;
use App\Shared\Enum\PlanAccessScope;
use App\Shared\Enum\SubscriptionStatus;
use App\Shared\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BookingServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject   $em;
    private ClassSessionRepository&MockObject   $sessionRepo;
    private BookingRepository&MockObject        $bookingRepo;
    private SubscriptionRepository&MockObject   $subRepo;
    private ConflictCheckerService&MockObject   $conflictChecker;
    private BookingService                      $service;

    protected function setUp(): void
    {
        $this->em             = $this->createMock(EntityManagerInterface::class);
        $this->sessionRepo    = $this->createMock(ClassSessionRepository::class);
        $this->bookingRepo    = $this->createMock(BookingRepository::class);
        $this->subRepo        = $this->createMock(SubscriptionRepository::class);
        $this->conflictChecker = $this->createMock(ConflictCheckerService::class);

        $this->service = new BookingService(
            $this->em,
            $this->sessionRepo,
            $this->bookingRepo,
            $this->subRepo,
            $this->conflictChecker,
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        $u = new User();
        $u->setEmail('member@gym.com')->setPassword('x')->setRole(UserRole::MEMBER)->setStatus('ACTIVE');
        return $u;
    }

    private function makeCoach(): User
    {
        $u = new User();
        $u->setEmail('coach@gym.com')->setPassword('x')->setRole(UserRole::COACH)->setStatus('ACTIVE');
        return $u;
    }

    private function makeSession(int $capacity = 10): ClassSession
    {
        $t = new \DateTimeImmutable('09:00:00');
        $e = new \DateTimeImmutable('10:00:00');
        $session = new ClassSession();
        $session->setName('Yoga')->setCoach($this->makeCoach())
                ->setRoom('Studio A')->setDayOfWeek('MONDAY')
                ->setStartTime($t)->setEndTime($e)->setCapacity($capacity);
        return $session;
    }

    private function makeActiveSubscription(User $member, PlanAccessScope $scope = PlanAccessScope::BOTH): Subscription
    {
        $plan = new Plan();
        $plan->setName('Standard')->setPrice(30)->setDurationDays(30)
             ->setAccessScope($scope)->setIsActive(true);

        $sub = new Subscription();
        $sub->setMember($member)->setPlan($plan)->setStatus(SubscriptionStatus::ACTIVE);
        $sub->setStartDate(new \DateTimeImmutable('today'));
        $sub->setEndDate(new \DateTimeImmutable('+30 days'));
        return $sub;
    }

    // ── bookClass ─────────────────────────────────────────────────────────────

    public function testBookClassCreatesConfirmedBookingWhenCapacityAvailable(): void
    {
        $member  = $this->makeUser();
        $session = $this->makeSession(10);
        $sub     = $this->makeActiveSubscription($member);

        $this->subRepo->method('findActiveSubscription')->willReturn($sub);
        $this->bookingRepo->method('findExistingBooking')->willReturn(null);
        $this->bookingRepo->method('countConfirmed')->willReturn(5); // under capacity
        $this->conflictChecker->method('validateMemberAvailability'); // no throw
        $this->bookingRepo->expects($this->once())->method('save');

        $booking = $this->service->bookClass($member, $session);

        $this->assertSame(BookingStatus::CONFIRMED, $booking->getStatus());
        $this->assertSame($member, $booking->getMember());
    }

    public function testBookClassCreatesWaitlistBookingWhenFull(): void
    {
        $member  = $this->makeUser();
        $session = $this->makeSession(5);
        $sub     = $this->makeActiveSubscription($member);

        $this->subRepo->method('findActiveSubscription')->willReturn($sub);
        $this->bookingRepo->method('findExistingBooking')->willReturn(null);
        $this->bookingRepo->method('countConfirmed')->willReturn(5); // at capacity
        $this->conflictChecker->method('validateMemberAvailability');
        $this->bookingRepo->method('save');

        $booking = $this->service->bookClass($member, $session);

        $this->assertSame(BookingStatus::WAITLIST, $booking->getStatus());
    }

    public function testBookClassThrowsWhenNoActiveSubscription(): void
    {
        $member  = $this->makeUser();
        $session = $this->makeSession();

        $this->subRepo->method('findActiveSubscription')->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Rule 7');
        $this->service->bookClass($member, $session);
    }

    public function testBookClassThrowsWhenPlanDoesNotAllowClasses(): void
    {
        $member  = $this->makeUser();
        $session = $this->makeSession();
        $sub     = $this->makeActiveSubscription($member, PlanAccessScope::OPEN_GYM);

        $this->subRepo->method('findActiveSubscription')->willReturn($sub);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Rule 7');
        $this->service->bookClass($member, $session);
    }

    public function testBookClassThrowsOnDuplicateBooking(): void
    {
        $member  = $this->makeUser();
        $session = $this->makeSession();
        $sub     = $this->makeActiveSubscription($member);
        $existing = new Booking($member, $session, BookingStatus::CONFIRMED);

        $this->subRepo->method('findActiveSubscription')->willReturn($sub);
        $this->bookingRepo->method('findExistingBooking')->willReturn($existing);

        $this->expectException(\LogicException::class);
        $this->service->bookClass($member, $session);
    }

    public function testBookClassThrowsOnScheduleConflict(): void
    {
        $member  = $this->makeUser();
        $session = $this->makeSession();
        $sub     = $this->makeActiveSubscription($member);

        $this->subRepo->method('findActiveSubscription')->willReturn($sub);
        $this->bookingRepo->method('findExistingBooking')->willReturn(null);
        $this->conflictChecker
            ->method('validateMemberAvailability')
            ->willThrowException(new \InvalidArgumentException('Booking conflict (Rule 13)'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule 13');
        $this->service->bookClass($member, $session);
    }

    // ── cancelBooking ─────────────────────────────────────────────────────────

    public function testCancelBookingCancelsConfirmedBookingAndPromotesWaitlist(): void
    {
        $member   = $this->makeUser();
        $session  = $this->makeSession();
        $booking  = new Booking($member, $session, BookingStatus::CONFIRMED);
        $waiting  = new Booking(new User(), $session, BookingStatus::WAITLIST);

        $this->bookingRepo->method('findFirstWaitlisted')->willReturn($waiting);
        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $promoted = $this->service->cancelBooking($member, $booking);

        $this->assertSame(BookingStatus::CANCELLED, $booking->getStatus());
        $this->assertSame(BookingStatus::CONFIRMED, $waiting->getStatus());
        $this->assertSame($waiting, $promoted);
    }

    public function testCancelBookingReturnNullIfNoWaitlist(): void
    {
        $member  = $this->makeUser();
        $session = $this->makeSession();
        $booking = new Booking($member, $session, BookingStatus::CONFIRMED);

        $this->bookingRepo->method('findFirstWaitlisted')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');

        $promoted = $this->service->cancelBooking($member, $booking);

        $this->assertNull($promoted);
    }

    public function testCancelBookingThrowsForWrongMember(): void
    {
        $owner  = $this->makeUser();
        $other  = new User();
        $other->setEmail('other@gym.com')->setPassword('x')->setRole(UserRole::MEMBER)->setStatus('ACTIVE');
        $session = $this->makeSession();
        $booking = new Booking($owner, $session, BookingStatus::CONFIRMED);

        $this->expectException(\LogicException::class);
        $this->service->cancelBooking($other, $booking);
    }

    public function testCancelBookingThrowsIfAlreadyCancelled(): void
    {
        $member  = $this->makeUser();
        $session = $this->makeSession();
        $booking = new Booking($member, $session, BookingStatus::CANCELLED);

        $this->expectException(\LogicException::class);
        $this->service->cancelBooking($member, $booking);
    }

    // ── createClassSession ────────────────────────────────────────────────────

    public function testCreateClassSessionThrowsWhenStartAfterEnd(): void
    {
        $coach = $this->makeCoach();
        $start = new \DateTimeImmutable('10:00');
        $end   = new \DateTimeImmutable('09:00');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->createClassSession('Yoga', $coach, 'Room A', 'MONDAY', $start, $end, 10);
    }

    public function testCreateClassSessionReturnsSessionOnSuccess(): void
    {
        $coach = $this->makeCoach();
        $start = new \DateTimeImmutable('09:00');
        $end   = new \DateTimeImmutable('10:00');

        $this->conflictChecker->method('validateCoachAvailability');
        $this->conflictChecker->method('validateRoomAvailability');
        $this->sessionRepo->expects($this->once())->method('save');

        $session = $this->service->createClassSession('Yoga', $coach, 'Room A', 'MONDAY', $start, $end, 15);

        $this->assertSame('Yoga', $session->getName());
        $this->assertSame(15, $session->getCapacity());
    }

    public function testCreateClassSessionThrowsOnCoachConflict(): void
    {
        $coach = $this->makeCoach();
        $this->conflictChecker
            ->method('validateCoachAvailability')
            ->willThrowException(new \InvalidArgumentException('Coach scheduling conflict (Rule 11)'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule 11');
        $this->service->createClassSession('Pilates', $coach, 'Room B', 'TUESDAY',
            new \DateTimeImmutable('09:00'), new \DateTimeImmutable('10:00'), 10);
    }

    public function testCreateClassSessionThrowsOnRoomConflict(): void
    {
        $coach = $this->makeCoach();
        $this->conflictChecker->method('validateCoachAvailability');
        $this->conflictChecker
            ->method('validateRoomAvailability')
            ->willThrowException(new \InvalidArgumentException('Room scheduling conflict (Rule 12)'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule 12');
        $this->service->createClassSession('Spin', $coach, 'Room C', 'WEDNESDAY',
            new \DateTimeImmutable('09:00'), new \DateTimeImmutable('10:00'), 10);
    }
}
