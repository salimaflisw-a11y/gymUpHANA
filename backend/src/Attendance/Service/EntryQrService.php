<?php

namespace App\Attendance\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EntryQrService
{
    private const PAYLOAD_PREFIX = 'GYMUP-CHECKIN:v1:';

    public function __construct(
        #[Autowire('%env(GYM_ENTRY_QR_TOKEN)%')]
        private readonly string $entryToken
    ) {
    }

    public function getPayload(): string
    {
        return self::PAYLOAD_PREFIX . $this->entryToken;
    }

    public function isValid(string $scannedPayload): bool
    {
        $scanned = trim($scannedPayload);

        return $scanned !== '' && hash_equals($this->getPayload(), $scanned);
    }
}
