<?php

namespace App\AIAdvisor\Service;

use App\Attendance\Repository\CheckInRepository;
use App\Auth\Repository\UserRepository;
use App\Membership\Repository\SubscriptionRepository;
use App\Shared\Enum\UserRole;

class RetentionEngineService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CheckInRepository $checkInRepository,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    /**
     * Identifies members with active subscriptions who have broken their gym routine.
     */
    public function scanAtRiskMembers(): array
    {
        $allMembers = $this->userRepository->findBy([
            'role' => UserRole::MEMBER,
            'status' => 'ACTIVE',
        ]);

        $now = new \DateTimeImmutable();
        $atRisk = [];

        foreach ($allMembers as $member) {
            $sub = $this->subscriptionRepository->findActiveSubscription($member);
            if (!$sub) {
                continue;
            }

            $lastCheckIn = $this->checkInRepository->findOneBy(
                ['member' => $member],
                ['scannedAt' => 'DESC']
            );

            $daysSinceLastVisit = null;
            if ($lastCheckIn) {
                $daysSinceLastVisit = $now->diff($lastCheckIn->getScannedAt())->days;
            } else {
                $daysSinceLastVisit = $now->diff($member->getCreatedAt())->days;
            }

            if ($daysSinceLastVisit >= 10) {
                $riskLevel = $daysSinceLastVisit >= 21 ? 'CRITICAL_CHURN' : ($daysSinceLastVisit >= 14 ? 'HIGH_RISK' : 'MODERATE_RISK');

                $atRisk[] = [
                    'memberId' => $member->getId(),
                    'name' => $member->getFullName() ?? $member->getEmail(),
                    'email' => $member->getEmail(),
                    'plan' => $sub->getPlan()?->getName(),
                    'daysSinceLastVisit' => $daysSinceLastVisit,
                    'lastVisitDate' => $lastCheckIn?->getScannedAt()->format('Y-m-d') ?? 'Never',
                    'riskLevel' => $riskLevel,
                    'suggestedAction' => match ($riskLevel) {
                        'CRITICAL_CHURN' => 'Send personal "We miss you" VIP discount / 1-on-1 coaching invite.',
                        'HIGH_RISK' => 'Automated friendly coach SMS: "Need a gym buddy this week?"',
                        default => 'Gentle nudge email with upcoming favorite classes.',
                    },
                ];
            }
        }

        // Sort descending by days absent
        usort($atRisk, fn($a, $b) => $b['daysSinceLastVisit'] <=> $a['daysSinceLastVisit']);

        return [
            'totalAtRisk' => count($atRisk),
            'members' => $atRisk,
        ];
    }
}

