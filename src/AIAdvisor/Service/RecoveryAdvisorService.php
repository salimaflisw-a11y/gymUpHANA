<?php

namespace App\AIAdvisor\Service;

use App\Attendance\Repository\CheckInRepository;
use App\Auth\Entity\User;
use App\Booking\Repository\BookingRepository;
use App\Equipment\Repository\EquipmentLogRepository;
use App\Shared\Enum\BookingStatus;

class RecoveryAdvisorService
{
    public function __construct(
        private readonly CheckInRepository $checkInRepository,
        private readonly EquipmentLogRepository $equipmentLogRepository,
        private readonly BookingRepository $bookingRepository
    ) {
    }

    /**
     * Calculates member's readiness score, fatigued vs fresh muscle groups, and daily recommendation.
     */
    public function calculateReadiness(User $member): array
    {
        $now = new \DateTimeImmutable();
        $threeDaysAgo = $now->modify('-3 days');

        // Check recent gym visits
        $recentCheckIns = $this->checkInRepository->createQueryBuilder('c')
            ->where('c.member = :member')
            ->andWhere('c.scannedAt >= :threeDaysAgo')
            ->setParameter('member', $member)
            ->setParameter('threeDaysAgo', $threeDaysAgo)
            ->getQuery()
            ->getResult();

        $visitCountPast3Days = count($recentCheckIns);

        // Check recent machine logs to identify targeted muscle groups
        $recentLogs = $this->equipmentLogRepository->createQueryBuilder('l')
            ->join('l.equipment', 'e')
            ->addSelect('e')
            ->where('l.member = :member')
            ->andWhere('l.loggedAt >= :threeDaysAgo')
            ->setParameter('member', $member)
            ->setParameter('threeDaysAgo', $threeDaysAgo)
            ->getQuery()
            ->getResult();

        $musclesTrained = [];
        foreach ($recentLogs as $log) {
            $cat = $log->getEquipment()->getCategory();
            $musclesTrained[$cat] = ($musclesTrained[$cat] ?? 0) + 1;
        }

        // Calculate baseline readiness
        $readinessScore = match ($visitCountPast3Days) {
            0 => 95, // Fully rested
            1 => 88, // Great recovery
            2 => 74, // Moderate fatigue
            3 => 58, // High fatigue
            default => 50,
        };

        // Categorize muscle readiness
        $muscleStatus = [
            'LEGS' => isset($musclesTrained['LEGS']) ? 'RECOVERING' : 'READY',
            'CHEST' => isset($musclesTrained['CHEST']) ? 'RECOVERING' : 'READY',
            'BACK' => isset($musclesTrained['BACK']) ? 'RECOVERING' : 'READY',
            'SHOULDERS' => isset($musclesTrained['SHOULDERS']) ? 'RECOVERING' : 'READY',
            'ARMS' => isset($musclesTrained['ARMS']) ? 'RECOVERING' : 'READY',
            'CORE' => 'READY',
        ];

        // Recommend ideal focus for today
        $freshMuscles = array_keys(array_filter($muscleStatus, fn($s) => $s === 'READY'));
        $recommendedFocus = !empty($freshMuscles) ? implode(' & ', array_slice($freshMuscles, 0, 2)) : 'Active Recovery & Stretching';

        // Check upcoming class bookings for overtraining alerts
        $upcomingBookings = $this->bookingRepository->findBy([
            'member' => $member,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $warnings = [];
        if ($readinessScore < 65 && count($upcomingBookings) > 0) {
            $warnings[] = '⚠️ High Fatigue Warning: You have trained hard in the last 48 hours. Consider light cardio or mobility today to maximize muscle protein synthesis.';
        }

        return [
            'readinessScore' => $readinessScore,
            'recoveryState' => match (true) {
                $readinessScore >= 85 => 'PRIME_PERFORMANCE',
                $readinessScore >= 70 => 'OPTIMAL',
                default => 'FATIGUED_RECOVERY',
            },
            'recentVisitsPast3Days' => $visitCountPast3Days,
            'muscleReadiness' => $muscleStatus,
            'recommendedWorkoutFocus' => $recommendedFocus,
            'aiAdvice' => match (true) {
                $readinessScore >= 85 => 'Your neuromuscular system is fully recharged. Excellent day to attempt a new PR in the weight room!',
                $readinessScore >= 70 => 'Good energy levels. Stick to moderate volume and focus on explosive eccentric reps.',
                default => 'Elevated systemic fatigue detected. Prioritize hydration, 8 hours sleep, and gentle active mobility.',
            },
            'warnings' => $warnings,
        ];
    }
}

