<?php

namespace App\Tests\Unit\Payment;

use App\Auth\Entity\User;
use App\Membership\Entity\Plan;
use App\Membership\Entity\Subscription;
use App\Payment\Entity\Payment;
use App\Payment\Repository\PaymentRepository;
use App\Payment\Service\PaymentService;
use App\Shared\Enum\PlanAccessScope;
use App\Shared\Enum\PaymentMethod;
use App\Shared\Enum\SubscriptionStatus;
use App\Shared\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private PaymentRepository&MockObject      $paymentRepo;
    private PaymentService                    $service;

    protected function setUp(): void
    {
        $this->em          = $this->createMock(EntityManagerInterface::class);
        $this->paymentRepo = $this->createMock(PaymentRepository::class);
        $this->service     = new PaymentService($this->em, $this->paymentRepo);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(UserRole $role = UserRole::MEMBER): User
    {
        $u = new User();
        $u->setEmail('user@gym.com')->setPassword('x')->setRole($role)->setStatus('ACTIVE');
        return $u;
    }

    private function makePlan(float $price = 50.0, int $days = 30, bool $active = true): Plan
    {
        $p = new Plan();
        $p->setName('Plan')->setPrice($price)->setDurationDays($days)
          ->setAccessScope(PlanAccessScope::BOTH)->setIsActive($active);
        return $p;
    }

    private function makeSubscription(User $member, Plan $plan, SubscriptionStatus $status = SubscriptionStatus::PENDING): Subscription
    {
        $sub = new Subscription();
        $sub->setMember($member)->setPlan($plan)->setStatus($status);
        return $sub;
    }

    // ── processOnlinePayment ──────────────────────────────────────────────────

    public function testOnlinePaymentActivatesSubscription(): void
    {
        $member = $this->makeUser();
        $plan   = $this->makePlan(50.0, 30);
        $sub    = $this->makeSubscription($member, $plan);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $payment = $this->service->processOnlinePayment($member, $sub);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame(PaymentMethod::ONLINE, $payment->getMethod());
        $this->assertSame('50.00', $payment->getAmount());
        $this->assertSame(SubscriptionStatus::ACTIVE, $sub->getStatus());
    }

    public function testOnlinePaymentThrowsForWrongMember(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $other->setEmail('other@gym.com');
        $plan = $this->makePlan();
        $sub  = $this->makeSubscription($owner, $plan);

        $this->expectException(\LogicException::class);
        $this->service->processOnlinePayment($other, $sub);
    }

    public function testOnlinePaymentThrowsIfAlreadyActive(): void
    {
        $member = $this->makeUser();
        $plan   = $this->makePlan();
        $sub    = $this->makeSubscription($member, $plan, SubscriptionStatus::ACTIVE);

        $this->expectException(\LogicException::class);
        $this->service->processOnlinePayment($member, $sub);
    }

    public function testOnlinePaymentThrowsIfPlanIsInactive(): void
    {
        $member = $this->makeUser();
        $plan   = $this->makePlan(active: false);
        $sub    = $this->makeSubscription($member, $plan);

        $this->expectException(\LogicException::class);
        $this->service->processOnlinePayment($member, $sub);
    }

    // ── processCashPayment ────────────────────────────────────────────────────

    public function testCashPaymentActivatesSubscription(): void
    {
        $staff  = $this->makeUser(UserRole::RECEPTIONIST);
        $member = $this->makeUser();
        $plan   = $this->makePlan(100.0, 30);
        $sub    = $this->makeSubscription($member, $plan);

        $this->em->method('persist');
        $this->em->method('flush');

        $payment = $this->service->processCashPayment($staff, $member, $sub);

        $this->assertSame(PaymentMethod::CASH, $payment->getMethod());
        $this->assertSame(SubscriptionStatus::ACTIVE, $sub->getStatus());
        $this->assertSame($staff, $payment->getProcessedBy());
    }

    public function testCashPaymentThrowsIfAmountTooLow(): void
    {
        $staff  = $this->makeUser(UserRole::RECEPTIONIST);
        $member = $this->makeUser();
        $plan   = $this->makePlan(100.0);
        $sub    = $this->makeSubscription($member, $plan);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->processCashPayment($staff, $member, $sub, 50.0); // only paid half
    }

    public function testCashPaymentAcceptsExactAmount(): void
    {
        $staff  = $this->makeUser(UserRole::RECEPTIONIST);
        $member = $this->makeUser();
        $plan   = $this->makePlan(100.0);
        $sub    = $this->makeSubscription($member, $plan);

        $this->em->method('persist');
        $this->em->method('flush');

        $payment = $this->service->processCashPayment($staff, $member, $sub, 100.0);
        $this->assertSame('100.00', $payment->getAmount());
    }

    public function testPaymentSetsCorrectSubscriptionDates(): void
    {
        $member = $this->makeUser();
        $plan   = $this->makePlan(50.0, 60);
        $sub    = $this->makeSubscription($member, $plan);

        $this->em->method('persist');
        $this->em->method('flush');

        $this->service->processOnlinePayment($member, $sub);

        $expected = (new \DateTimeImmutable('today'))->modify('+60 days');
        $this->assertSame($expected->format('Y-m-d'), $sub->getEndDate()->format('Y-m-d'));
    }
}
