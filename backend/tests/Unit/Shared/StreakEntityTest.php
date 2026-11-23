<?php

namespace App\Tests\Unit\Shared;

use App\Attendance\Entity\Streak;
use App\Auth\Entity\User;
use PHPUnit\Framework\TestCase;

class StreakEntityTest extends TestCase
{
    private function makeStreak(): Streak
    {
        $user = new User();
        return new Streak($user);
    }

    public function testNewStreakStartsAtZero(): void
    {
        $streak = $this->makeStreak();
        $this->assertSame(0, $streak->getCurrentStreak());
        $this->assertNull($streak->getLastSuccessDate());
    }

    public function testIncrementStreak(): void
    {
        $streak = $this->makeStreak();
        $streak->setCurrentStreak(5);
        $streak->incrementStreak();
        $this->assertSame(6, $streak->getCurrentStreak());
    }

    public function testResetStreak(): void
    {
        $streak = $this->makeStreak();
        $streak->setCurrentStreak(10);
        $streak->resetStreak();
        $this->assertSame(0, $streak->getCurrentStreak());
    }

    public function testSetCurrentStreakCannotGoBelowZero(): void
    {
        $streak = $this->makeStreak();
        $streak->setCurrentStreak(-5);
        $this->assertSame(0, $streak->getCurrentStreak());
    }

    public function testSetLastSuccesDateNormalisesToMidnight(): void
    {
        $streak = $this->makeStreak();
        $date = new \DateTimeImmutable('2024-06-15 14:30:00');
        $streak->setLastSuccessDate($date);
        $this->assertSame('00:00:00', $streak->getLastSuccessDate()->format('H:i:s'));
        $this->assertSame('2024-06-15', $streak->getLastSuccessDate()->format('Y-m-d'));
    }
}
