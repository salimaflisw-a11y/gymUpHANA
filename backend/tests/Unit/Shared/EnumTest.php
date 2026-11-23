<?php

namespace App\Tests\Unit\Shared;

use App\Shared\Enum\PlanAccessScope;
use App\Shared\Enum\PositionType;
use App\Shared\Enum\UserRole;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    // ── PlanAccessScope ──────────────────────────────────────────────────────

    public function testOpenGymScopeAllowsOpenGymButNotClasses(): void
    {
        $scope = PlanAccessScope::OPEN_GYM;
        $this->assertTrue($scope->allowsOpenGym());
        $this->assertFalse($scope->allowsClasses());
    }

    public function testClassesScopeAllowsClassesButNotOpenGym(): void
    {
        $scope = PlanAccessScope::CLASSES;
        $this->assertFalse($scope->allowsOpenGym());
        $this->assertTrue($scope->allowsClasses());
    }

    public function testBothScopeAllowsEverything(): void
    {
        $scope = PlanAccessScope::BOTH;
        $this->assertTrue($scope->allowsOpenGym());
        $this->assertTrue($scope->allowsClasses());
    }

    // ── PositionType ─────────────────────────────────────────────────────────

    public function testCoachPositionMapsToCoachRole(): void
    {
        $this->assertSame(UserRole::COACH, PositionType::COACH->toUserRole());
    }

    public function testReceptionistPositionMapsToReceptionistRole(): void
    {
        $this->assertSame(UserRole::RECEPTIONIST, PositionType::RECEPTIONIST->toUserRole());
    }
}
