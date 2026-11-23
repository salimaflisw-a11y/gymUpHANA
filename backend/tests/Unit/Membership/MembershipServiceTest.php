<?php

namespace App\Tests\Unit\Membership;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Membership\Entity\Plan;
use App\Membership\Entity\Subscription;
use App\Membership\Repository\PlanRepository;
use App\Membership\Repository\SubscriptionRepository;
use App\Membership\Service\MembershipService;
use App\Shared\Enum\PlanAccessScope;
use App\Shared\Enum\SubscriptionStatus;
use App\Shared\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MembershipServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject    $em;
    private PlanRepository&MockObject            $planRepo;
    private SubscriptionRepository&MockObject    $subRepo;
    private UserRepository&MockObject            $userRepo;
    private UserPasswordHasherInterface&MockObject $hasher;
    private MembershipService $service;

    protected function setUp(): void
    {
        $this->em       = $this->createMock(EntityManagerInterface::class);
        $this->planRepo = $this->createMock(PlanRepository::class);
        $this->subRepo  = $this->createMock(SubscriptionRepository::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->hasher   = $this->createMock(UserPasswordHasherInterface::class);

        $this->service = new MembershipService(
            $this->em,
            $this->planRepo,
            $this->subRepo,
            $this->userRepo,
            $this->hasher,
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeActivePlan(int $days = 30, ?int $maxMembers = null): Plan
    {
        $plan = new Plan();
        $plan->setName('Standard')
             ->setPrice(29.99)
             ->setDurationDays($days)
             ->setAccessScope(PlanAccessScope::BOTH)
             ->setMaxMembers($maxMembers)
             ->setIsActive(true);
        return $plan;
    }

    private function makeMember(string $email = 'member@gym.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRole(UserRole::MEMBER);
        $user->setStatus('ACTIVE');
        $user->setPassword('hash');
        return $user;
    }

    // ── createPlan ───────────────────────────────────────────────────────────

    public function testCreatePlanSavesAndReturnsPlan(): void
    {
        $this->planRepo->expects($this->once())->method('save');

        $plan = $this->service->createPlan('Gold', 49.99, 30);

        $this->assertSame('Gold', $plan->getName());
        $this->assertSame('49.99', $plan->getPrice());
        $this->assertTrue($plan->isActive());
    }

    public function testCreatePlanWithMaxMembersCapacity(): void
    {
        $this->planRepo->method('save');
        $plan = $this->service->createPlan('Limited', 99.0, 30, PlanAccessScope::BOTH, 50);
        $this->assertSame(50, $plan->getMaxMembers());
    }

    // ── deactivatePlan / activatePlan ─────────────────────────────────────────

    public function testDeactivatePlanSetsPlanInactive(): void
    {
        $plan = $this->makeActivePlan();
        $this->planRepo->expects($this->once())->method('save')->with($plan);

        $this->service->deactivatePlan($plan);

        $this->assertFalse($plan->isActive());
    }

    public function testActivatePlanSetsPlanActive(): void
    {
        $plan = $this->makeActivePlan();
        $plan->setIsActive(false);
        $this->planRepo->expects($this->once())->method('save')->with($plan);

        $this->service->activatePlan($plan);

        $this->assertTrue($plan->isActive());
    }

    // ── subscribeMember ───────────────────────────────────────────────────────

    public function testSubscribeMemberCreatesSubscriptionWithPendingStatus(): void
    {
        $plan   = $this->makeActivePlan(30);
        $member = $this->makeMember();
        $this->subRepo->method('count')->willReturn(0);
        $this->subRepo->expects($this->once())->method('save');

        $sub = $this->service->subscribeMember($member, $plan);

        $this->assertInstanceOf(Subscription::class, $sub);
        $this->assertSame(SubscriptionStatus::PENDING, $sub->getStatus());
        $this->assertSame($member, $sub->getMember());
        $this->assertSame($plan, $sub->getPlan());
    }

    public function testSubscribeMemberSetsCorrectEndDate(): void
    {
        $plan   = $this->makeActivePlan(30);
        $member = $this->makeMember();
        $this->subRepo->method('count')->willReturn(0);
        $this->subRepo->method('save');

        $sub = $this->service->subscribeMember($member, $plan);

        $expected = (new \DateTimeImmutable('today'))->modify('+30 days');
        $this->assertSame($expected->format('Y-m-d'), $sub->getEndDate()->format('Y-m-d'));
    }

    public function testSubscribeMemberThrowsWhenPlanIsInactive(): void
    {
        $plan = $this->makeActivePlan();
        $plan->setIsActive(false);

        $this->expectException(\LogicException::class);
        $this->service->subscribeMember($this->makeMember(), $plan);
    }

    public function testSubscribeMemberThrowsWhenCapReached(): void
    {
        $plan = $this->makeActivePlan(30, 2);
        $this->subRepo->method('count')->willReturn(2);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Rule 10');
        $this->service->subscribeMember($this->makeMember(), $plan);
    }

    public function testSubscribeMemberSucceedsWhenCapNotReached(): void
    {
        $plan = $this->makeActivePlan(30, 5);
        $this->subRepo->method('count')->willReturn(4); // 4 out of 5
        $this->subRepo->method('save');

        $sub = $this->service->subscribeMember($this->makeMember(), $plan);
        $this->assertSame(SubscriptionStatus::PENDING, $sub->getStatus());
    }

    // ── registerWalkInMember ──────────────────────────────────────────────────

    public function testRegisterWalkInMemberCreatesNewUserWhenEmailNotFound(): void
    {
        $plan = $this->makeActivePlan();
        $this->userRepo->method('findOneBy')->willReturn(null);
        $this->hasher->method('hashPassword')->willReturn('hashed_pass');
        $this->userRepo->method('save');
        $this->subRepo->method('count')->willReturn(0);
        $this->subRepo->method('save');

        $result = $this->service->registerWalkInMember('new@gym.com', 'New User', $plan);

        $this->assertInstanceOf(User::class, $result['member']);
        $this->assertSame('new@gym.com', $result['member']->getEmail());
        $this->assertNotNull($result['temporaryPassword']);
    }

    public function testRegisterWalkInMemberReusesExistingUser(): void
    {
        $plan     = $this->makeActivePlan();
        $existing = $this->makeMember('existing@gym.com');

        $this->userRepo->method('findOneBy')->willReturn($existing);
        $this->subRepo->method('count')->willReturn(0);
        $this->subRepo->method('save');

        $result = $this->service->registerWalkInMember('existing@gym.com', 'Existing', $plan);

        $this->assertSame($existing, $result['member']);
        $this->assertNull($result['temporaryPassword']);
    }

    // ── renewSubscription ─────────────────────────────────────────────────────

    public function testRenewSubscriptionThrowsWhenPlanIsInactive(): void
    {
        $plan = $this->makeActivePlan();
        $plan->setIsActive(false);

        $this->expectException(\LogicException::class);
        $this->service->renewSubscription($this->makeMember(), $plan);
    }

    public function testRenewSubscriptionCreatesNewSubscription(): void
    {
        $plan   = $this->makeActivePlan(90);
        $member = $this->makeMember();

        $this->subRepo->method('findActiveSubscription')->willReturn(null);
        $this->subRepo->method('count')->willReturn(0);
        $this->subRepo->method('save');

        $sub = $this->service->renewSubscription($member, $plan);

        $this->assertSame(SubscriptionStatus::PENDING, $sub->getStatus());
        $expected = (new \DateTimeImmutable('today'))->modify('+90 days');
        $this->assertSame($expected->format('Y-m-d'), $sub->getEndDate()->format('Y-m-d'));
    }
}
