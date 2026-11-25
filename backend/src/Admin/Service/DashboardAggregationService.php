<?php

namespace App\Admin\Service;

use App\Attendance\Repository\StreakRepository;
use App\Auth\Repository\UserRepository;
use App\Booking\Repository\ClassSessionRepository;
use App\Membership\Repository\SubscriptionRepository;
use App\Membership\Service\MembershipService;
use App\Payment\Service\PaymentService;
use App\Shared\Enum\UserRole;

class DashboardAggregationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly ClassSessionRepository $classSessionRepository,
        private readonly StreakRepository $streakRepository,
        private readonly MembershipService $membershipService,
        private readonly PaymentService $paymentService
    ) {
    }

    /**
     * Aggregates key metrics for the Admin Dashboard:
     * - Active members count and 30-day growth
     * - Class occupancy rates
     * - Monthly revenue (online vs cash, month-over-month change)
     * - Expiring memberships
     * - Member streak activity
     */
    public function getDashboardMetrics(): array
    {
        $today = new \DateTimeImmutable('today');

        // 1. Active members (distinct members with a valid active subscription)
        $activeMembersCount = $this->subscriptionRepository->countActiveMembers();
        $activeMembersThirtyDaysAgo = $this->subscriptionRepository->countMembersActiveOn($today->modify('-30 days'));

        $activeMembersChangePercent = $activeMembersThirtyDaysAgo > 0
            ? round((($activeMembersCount - $activeMembersThirtyDaysAgo) / $activeMembersThirtyDaysAgo) * 100, 1)
            : null;

        $totalMembersCount = $this->userRepository->count([
            'role' => UserRole::MEMBER,
        ]);

        $firstDayOfMonth = $today->modify('first day of this month');
        $newMembersThisMonth = $this->userRepository->countMembersRegisteredBetween($firstDayOfMonth, $firstDayOfMonth->modify('+1 month'));

        // 2. Class occupancy rates
        $classSessions = $this->classSessionRepository->findAll();
        $totalCapacity = 0;
        $totalConfirmed = 0;
        $classDetails = [];

        foreach ($classSessions as $cs) {
            $confirmed = $cs->getConfirmedBookingsCount();
            $cap = $cs->getCapacity();
            $totalCapacity += $cap;
            $totalConfirmed += $confirmed;

            $rate = ($cap > 0) ? round(($confirmed / $cap) * 100, 1) : 0;
            $classDetails[] = [
                'id' => $cs->getId(),
                'name' => $cs->getName(),
                'room' => $cs->getRoom(),
                'dayOfWeek' => $cs->getDayOfWeek(),
                'capacity' => $cap,
                'confirmed' => $confirmed,
                'occupancyRatePercent' => $rate,
            ];
        }

        $overallOccupancyRate = ($totalCapacity > 0)
            ? round(($totalConfirmed / $totalCapacity) * 100, 1)
            : 0.0;

        // 3. Monthly revenue with month-over-month comparison
        $monthlyRevenue = $this->paymentService->getRevenueReport($firstDayOfMonth);

        $previousMonthStart = $firstDayOfMonth->modify('-1 month');
        $previousMonthEnd = $firstDayOfMonth->modify('-1 second');
        $previousMonthRevenue = $this->paymentService->getRevenueReport($previousMonthStart, $previousMonthEnd);

        $monthlyRevenue['previousMonthRevenue'] = $previousMonthRevenue['totalRevenue'];
        $monthlyRevenue['changePercent'] = $previousMonthRevenue['totalRevenue'] > 0
            ? round((($monthlyRevenue['totalRevenue'] - $previousMonthRevenue['totalRevenue']) / $previousMonthRevenue['totalRevenue']) * 100, 1)
            : null;

        // 4. Expiring memberships
        $expiringSubscriptions = $this->membershipService->getExpiringSubscriptions(7);
        $expiringCount = count($expiringSubscriptions);
        $expiringSharePercent = $activeMembersCount > 0
            ? min(100, round(($expiringCount / $activeMembersCount) * 100, 1))
            : ($expiringCount > 0 ? 100.0 : 0.0);

        // 5. Member streak activity
        $topStreaks = $this->streakRepository->findTopStreaks(5);
        $streakLeaderboard = array_map(function ($s) {
            return [
                'memberId' => $s->getMember()->getId(),
                'memberName' => $s->getMember()->getFullName() ?? $s->getMember()->getEmail(),
                'streak' => $s->getCurrentStreak(),
                'lastSuccessDate' => $s->getLastSuccessDate()?->format('Y-m-d'),
            ];
        }, $topStreaks);

        return [
            'activeMembersCount' => $activeMembersCount,
            'activeMembersThirtyDaysAgo' => $activeMembersThirtyDaysAgo,
            'activeMembersChangePercent' => $activeMembersChangePercent,
            'totalRegisteredMembers' => $totalMembersCount,
            'newMembersThisMonth' => $newMembersThisMonth,
            'overallOccupancyRatePercent' => $overallOccupancyRate,
            'totalConfirmedBookings' => $totalConfirmed,
            'totalCapacity' => $totalCapacity,
            'classSessionCount' => count($classSessions),
            'classes' => $classDetails,
            'revenueThisMonth' => $monthlyRevenue,
            'expiringMembershipsCount' => $expiringCount,
            'expiringMembershipsSharePercent' => $expiringSharePercent,
            'topStreaks' => $streakLeaderboard,
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }
}
