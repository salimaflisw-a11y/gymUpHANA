<?php

namespace App\Tests\Unit\Membership;

use App\Membership\Entity\Plan;
use App\Shared\Enum\PlanAccessScope;
use PHPUnit\Framework\TestCase;

class PlanEntityTest extends TestCase
{
    private function makePlan(
        string $name = 'Basic',
        float $price = 29.99,
        int $days = 30
    ): Plan {
        $plan = new Plan();
        $plan->setName($name)
             ->setPrice($price)
             ->setDurationDays($days)
             ->setIsActive(true);
        return $plan;
    }

    public function testSetPriceFormatsToTwoDecimals(): void
    {
        $plan = $this->makePlan(price: 50);
        $this->assertSame('50.00', $plan->getPrice());
    }

    public function testPlanIsActiveByDefault(): void
    {
        $plan = $this->makePlan();
        $this->assertTrue($plan->isActive());
    }

    public function testDeactivatePlan(): void
    {
        $plan = $this->makePlan();
        $plan->setIsActive(false);
        $this->assertFalse($plan->isActive());
    }

    public function testAccessScopeDefaultsToBot(): void
    {
        $plan = new Plan();
        // No scope set → default from property declaration
        $plan->setName('x')->setPrice(10)->setDurationDays(30)->setIsActive(true);
        // accessScope is BOTH by default
        $this->assertSame(PlanAccessScope::BOTH, $plan->getAccessScope());
    }

    public function testMaxMembersNullByDefault(): void
    {
        $plan = $this->makePlan();
        $this->assertNull($plan->getMaxMembers());
    }
}
