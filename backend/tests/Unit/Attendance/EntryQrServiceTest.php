<?php

namespace App\Tests\Unit\Attendance;

use App\Attendance\Service\EntryQrService;
use PHPUnit\Framework\TestCase;

class EntryQrServiceTest extends TestCase
{
    private const TOKEN = 'GYMUP-ENTRANCE-TEST';

    private EntryQrService $service;

    protected function setUp(): void
    {
        $this->service = new EntryQrService(self::TOKEN);
    }

    public function testPayloadEmbedsToken(): void
    {
        $this->assertSame('GYMUP-CHECKIN:v1:' . self::TOKEN, $this->service->getPayload());
    }

    public function testAcceptsItsOwnPayload(): void
    {
        $this->assertTrue($this->service->isValid($this->service->getPayload()));
    }

    public function testAcceptsPayloadWithSurroundingWhitespace(): void
    {
        $this->assertTrue($this->service->isValid('  ' . $this->service->getPayload() . " \n"));
    }

    public function testRejectsArbitraryText(): void
    {
        $this->assertFalse($this->service->isValid('https://example.com'));
    }

    public function testRejectsPayloadWithWrongToken(): void
    {
        $this->assertFalse($this->service->isValid('GYMUP-CHECKIN:v1:WRONG-TOKEN'));
    }

    public function testRejectsPayloadWithWrongPrefix(): void
    {
        $this->assertFalse($this->service->isValid('SOMETHING:v1:' . self::TOKEN));
    }

    public function testRejectsEmptyString(): void
    {
        $this->assertFalse($this->service->isValid('   '));
    }
}
