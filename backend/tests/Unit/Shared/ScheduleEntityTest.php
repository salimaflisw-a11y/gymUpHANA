<?php

namespace App\Tests\Unit\Shared;

use App\Attendance\Entity\Schedule;
use PHPUnit\Framework\TestCase;

class ScheduleEntityTest extends TestCase
{
    private function makeSchedule(): Schedule
    {
        return new Schedule();
    }

    public function testSetWeekDaysNormalisesToUppercase(): void
    {
        $schedule = $this->makeSchedule();
        $schedule->setWeekDays(['monday', 'Wednesday', 'FRIDAY']);
        $this->assertSame(['MONDAY', 'WEDNESDAY', 'FRIDAY'], $schedule->getWeekDays());
    }

    public function testSetWeekDaysRemovesDuplicates(): void
    {
        $schedule = $this->makeSchedule();
        $schedule->setWeekDays(['MONDAY', 'monday', 'MONDAY']);
        $this->assertCount(1, $schedule->getWeekDays());
    }

    public function testSetWeekDaysIgnoresInvalidDays(): void
    {
        $schedule = $this->makeSchedule();
        $schedule->setWeekDays(['MONDAY', 'FUNDAY', 'SATURDAY']);
        $this->assertSame(['MONDAY', 'SATURDAY'], $schedule->getWeekDays());
    }

    public function testIncludesDayReturnsTrueForScheduledDay(): void
    {
        $schedule = $this->makeSchedule();
        $schedule->setWeekDays(['TUESDAY', 'THURSDAY']);
        $this->assertTrue($schedule->includesDay('tuesday'));
        $this->assertTrue($schedule->includesDay('THURSDAY'));
    }

    public function testIncludesDayReturnsFalseForUnscheduledDay(): void
    {
        $schedule = $this->makeSchedule();
        $schedule->setWeekDays(['MONDAY']);
        $this->assertFalse($schedule->includesDay('SUNDAY'));
    }
}
