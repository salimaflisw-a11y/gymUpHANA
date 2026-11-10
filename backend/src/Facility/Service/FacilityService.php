<?php

namespace App\Facility\Service;

use App\Attendance\Repository\CheckInRepository;
use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Membership\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class FacilityService
{
    private const GYM_MAX_CAPACITY = 120;
    private const AVERAGE_WORKOUT_MINUTES = 75;
    private const ACCESS_TOKEN_SECRET = 'gymup_secure_turnstile_seed_salt_2026';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CheckInRepository $checkInRepository,
        private readonly UserRepository $userRepository,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    /**
     * Calculates real-time gym occupancy based on active check-ins in the current workout window.
     */
    public function getCurrentOccupancy(): array
    {
        $now = new \DateTimeImmutable();
        $windowStart = $now->modify(sprintf('-%d minutes', self::AVERAGE_WORKOUT_MINUTES));

        $count = $this->checkInRepository->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.member)')
            ->where('c.scannedAt >= :windowStart')
            ->setParameter('windowStart', $windowStart)
            ->getQuery()
            ->getSingleScalarResult();

        $activeCount = (int) $count;
        $maxCapacity = self::GYM_MAX_CAPACITY;
        $percentage = round(($activeCount / max(1, $maxCapacity)) * 100, 1);

        $status = match (true) {
            $percentage < 35 => 'QUIET',
            $percentage < 70 => 'OPTIMAL',
            $percentage < 85 => 'BUSY',
            default => 'PEAK',
        };

        // Zone-by-zone estimated distribution
        $zones = [
            [
                'zoneName' => 'Strength & Free Weights Area',
                'density' => min(100, (int) round($percentage * 1.15)),
                'status' => $percentage > 65 ? 'BUSY' : 'AVAILABLE',
            ],
            [
                'zoneName' => 'Cardio Deck & Treadmills',
                'density' => min(100, (int) round($percentage * 0.85)),
                'status' => 'AVAILABLE',
            ],
            [
                'zoneName' => 'Functional Turf & Calisthenics',
                'density' => min(100, (int) round($percentage * 0.95)),
                'status' => 'AVAILABLE',
            ],
            [
                'zoneName' => 'Group Fitness Studio',
                'density' => min(100, (int) round($percentage * 0.60)),
                'status' => 'OPEN',
            ],
        ];

        return [
            'activeMembersCount' => $activeCount,
            'maxCapacity' => $maxCapacity,
            'occupancyPercentage' => $percentage,
            'status' => $status,
            'statusMessage' => match ($status) {
                'QUIET' => 'Great time to train! Lots of free equipment available.',
                'OPTIMAL' => 'Steady flow. Most machines and benches are accessible.',
                'BUSY' => 'Popular workout time. Expect brief waits on core free weights.',
                'PEAK' => 'High gym volume right now. Plan your routine or visit off-peak.',
            },
            'zones' => $zones,
            'asOf' => $now->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Produces weekly historical crowd distribution by day of week and hour.
     */
    public function getWeeklyTrafficForecast(): array
    {
        $days = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];
        $scheduleForecast = [];

        // Realistic calibrated occupancy curve for gyms
        $basePattern = [
            6 => 25, 7 => 45, 8 => 60, 9 => 40, 10 => 35, 11 => 40,
            12 => 55, 13 => 50, 14 => 30, 15 => 35, 16 => 55, 17 => 80,
            18 => 95, 19 => 90, 20 => 75, 21 => 45, 22 => 20
        ];

        foreach ($days as $day) {
            $isWeekend = in_array($day, ['SATURDAY', 'SUNDAY'], true);
            $dayForecast = [];

            foreach ($basePattern as $hour => $baseVal) {
                $multiplier = $isWeekend ? ($hour >= 10 && $hour <= 15 ? 1.2 : 0.5) : 1.0;
                $predictedPercent = min(100, max(5, (int) round($baseVal * $multiplier)));
                $dayForecast[] = [
                    'hour' => sprintf('%02d:00', $hour),
                    'occupancyRate' => $predictedPercent,
                    'traffic' => $predictedPercent >= 75 ? 'PEAK' : ($predictedPercent >= 50 ? 'MODERATE' : 'QUIET'),
                ];
            }

            $scheduleForecast[$day] = $dayForecast;
        }

        return [
            'forecast' => $scheduleForecast,
            'bestTimeToTrain' => [
                'weekday' => '13:00 - 15:30 (Quiet, 30% occupancy)',
                'evening' => '20:30 - 22:00 (Less busy post-rush)',
                'weekend' => '07:00 - 09:30 or after 17:00',
            ]
        ];
    }

    /**
     * Generates a 30-second rolling dynamic QR token for gym turnstile access.
     */
    public function generateDynamicPass(User $member): array
    {
        $subscription = $this->subscriptionRepository->findActiveSubscription($member);
        if (!$subscription || !$subscription->getPlan()?->getAccessScope()->allowsOpenGym()) {
            throw new LogicException('Active membership with open gym access is required to generate an access pass.');
        }

        $now = time();
        $windowSeconds = 30;
        $timeWindow = (int) floor($now / $windowSeconds);
        $secondsRemaining = $windowSeconds - ($now % $windowSeconds);

        $payload = sprintf('%d:%d', $member->getId(), $timeWindow);
        $signature = hash_hmac('sha256', $payload, self::ACCESS_TOKEN_SECRET);
        $token = sprintf('%d.%d.%s', $member->getId(), $timeWindow, substr($signature, 0, 16));

        return [
            'memberId' => $member->getId(),
            'memberName' => $member->getFullName() ?? $member->getEmail(),
            'passToken' => $token,
            'expiresInSeconds' => $secondsRemaining,
            'windowSeconds' => $windowSeconds,
            'qrRawPayload' => sprintf('GYMUP://PASS/%s', $token),
        ];
    }

    /**
     * Validates a dynamic access pass presented at the door scanner.
     */
    public function verifyDynamicPass(string $passToken): array
    {
        $parts = explode('.', $passToken);
        if (count($parts) !== 3) {
            return ['valid' => false, 'reason' => 'Malformed access token'];
        }

        [$memberIdStr, $timeWindowStr, $providedSig] = $parts;
        $memberId = (int) $memberIdStr;
        $tokenWindow = (int) $timeWindowStr;

        $currentTimeWindow = (int) floor(time() / 30);
        // Allow ±1 window for network jitter / clock drift
        if (abs($currentTimeWindow - $tokenWindow) > 1) {
            return ['valid' => false, 'reason' => 'Pass expired. Please refresh the QR code in your app.'];
        }

        $expectedPayload = sprintf('%d:%d', $memberId, $tokenWindow);
        $expectedSignature = substr(hash_hmac('sha256', $expectedPayload, self::ACCESS_TOKEN_SECRET), 0, 16);

        if (!hash_equals($expectedSignature, $providedSig)) {
            return ['valid' => false, 'reason' => 'Invalid or forged security signature.'];
        }

        $member = $this->userRepository->find($memberId);
        if (!$member || !$member->isActive()) {
            return ['valid' => false, 'reason' => 'Member account is inactive or not found.'];
        }

        $subscription = $this->subscriptionRepository->findActiveSubscription($member);
        if (!$subscription) {
            return ['valid' => false, 'reason' => 'No active gym subscription found.'];
        }

        return [
            'valid' => true,
            'member' => [
                'id' => $member->getId(),
                'fullName' => $member->getFullName(),
                'email' => $member->getEmail(),
                'plan' => $subscription->getPlan()?->getName(),
            ],
            'authorizedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }
}

