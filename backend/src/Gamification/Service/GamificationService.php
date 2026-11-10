<?php

namespace App\Gamification\Service;

use App\Attendance\Repository\CheckInRepository;
use App\Attendance\Repository\StreakRepository;
use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Gamification\Entity\Badge;
use App\Gamification\Entity\ChallengeParticipant;
use App\Gamification\Entity\CommunityChallenge;
use App\Gamification\Entity\MemberBadge;
use App\Gamification\Repository\BadgeRepository;
use App\Gamification\Repository\ChallengeParticipantRepository;
use App\Gamification\Repository\CommunityChallengeRepository;
use App\Gamification\Repository\MemberBadgeRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class GamificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BadgeRepository $badgeRepository,
        private readonly MemberBadgeRepository $memberBadgeRepository,
        private readonly CommunityChallengeRepository $challengeRepository,
        private readonly ChallengeParticipantRepository $participantRepository,
        private readonly CheckInRepository $checkInRepository,
        private readonly StreakRepository $streakRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * Seeds initial gym achievement badges if database is empty.
     */
    public function seedDefaultBadges(): void
    {
        $existing = $this->badgeRepository->findAll();
        if (!empty($existing)) {
            return;
        }

        $defaults = [
            [
                'code' => 'FIRST_STEP',
                'name' => 'First Step',
                'description' => 'Complete your very first gym check-in.',
                'icon' => 'zap',
                'category' => 'ATTENDANCE',
                'requiredStreak' => 0,
                'requiredVisits' => 1,
                'perkReward' => 'Free GymUp shaker bottle at reception'
            ],
            [
                'code' => 'STREAK_3',
                'name' => 'Ignition',
                'description' => 'Maintain a 3-workout schedule streak.',
                'icon' => 'flame',
                'category' => 'STREAK',
                'requiredStreak' => 3,
                'requiredVisits' => 0,
                'perkReward' => null
            ],
            [
                'code' => 'STREAK_7',
                'name' => 'Unstoppable Momentum',
                'description' => 'Hold a 7-workout streak without missing your planned day.',
                'icon' => 'award',
                'category' => 'STREAK',
                'requiredStreak' => 7,
                'requiredVisits' => 0,
                'perkReward' => '1 Free Protein Smoothie at the Express Bar'
            ],
            [
                'code' => 'STREAK_30',
                'name' => 'Iron Habit',
                'description' => 'Crush a 30-workout schedule streak. Elite level discipline.',
                'icon' => 'shield',
                'category' => 'STREAK',
                'requiredStreak' => 30,
                'requiredVisits' => 0,
                'perkReward' => '1 Complimentary 1-on-1 Personal Training Session'
            ],
            [
                'code' => 'VISITS_25',
                'name' => 'Dedicated Regular',
                'description' => 'Log 25 total gym check-ins.',
                'icon' => 'activity',
                'category' => 'ATTENDANCE',
                'requiredStreak' => 0,
                'requiredVisits' => 25,
                'perkReward' => '1 Free Guest Day Pass for a friend'
            ],
            [
                'code' => 'CENTURION_100',
                'name' => 'The Centurion',
                'description' => 'Reach 100 lifetime gym workouts at GymUp.',
                'icon' => 'crown',
                'category' => 'ATTENDANCE',
                'requiredStreak' => 0,
                'requiredVisits' => 100,
                'perkReward' => 'Official GymUp Gold Member Hoodie & 10% next renewal'
            ],
        ];

        foreach ($defaults as $def) {
            $badge = new Badge(
                code: $def['code'],
                name: $def['name'],
                description: $def['description'],
                icon: $def['icon'],
                category: $def['category'],
                requiredStreak: $def['requiredStreak'],
                requiredVisits: $def['requiredVisits'],
                perkReward: $def['perkReward']
            );
            $this->entityManager->persist($badge);
        }

        $this->entityManager->flush();
    }

    /**
     * Seeds default active community challenges if none exist.
     */
    public function seedDefaultChallenges(): void
    {
        $existing = $this->challengeRepository->findActiveChallenges();
        if (!empty($existing)) {
            return;
        }

        $now = new \DateTimeImmutable();
        $end = $now->modify('+30 days');

        $c1 = new CommunityChallenge(
            title: 'Spring Shred 500 Check-Ins',
            description: 'Can the GymUp community reach 500 total gym visits this month? Every workout you log counts!',
            targetGoal: 500,
            startDate: $now,
            endDate: $end,
            goalMetric: 'TOTAL_VISITS',
            rewardDescription: 'Free GymUp Gold Shaker & 15% supplement discount for all participants'
        );

        $c2 = new CommunityChallenge(
            title: 'Titan Squad 10,000 Squat Volume',
            description: 'Collective gym strength milestone: log heavy leg day sets on the squat rack and leg press.',
            targetGoal: 10000,
            startDate: $now,
            endDate: $end,
            goalMetric: 'TOTAL_REPS',
            rewardDescription: '1 Free Electrolyte Refuel pack & custom Titan Badge'
        );

        $c3 = new CommunityChallenge(
            title: '30-Day Iron Consistency Blitz',
            description: 'Maintain high attendance across all studios and group classes.',
            targetGoal: 250,
            startDate: $now,
            endDate: $end,
            goalMetric: 'STUDIO_HOURS',
            rewardDescription: 'VIP Express Check-in Pass & Personal Coach Consultation'
        );

        $this->entityManager->persist($c1);
        $this->entityManager->persist($c2);
        $this->entityManager->persist($c3);
        $this->entityManager->flush();
    }

    /**
     * Evaluates member's attendance and streak stats and awards earned badges.
     *
     * @return list<Badge> Newly awarded badges
     */
    public function evaluateMemberAchievements(User $member): array
    {
        $this->seedDefaultBadges();

        $streakEntity = $this->streakRepository->findByMember($member);
        $currentStreak = $streakEntity ? $streakEntity->getCurrentStreak() : 0;
        $totalVisits = $this->checkInRepository->countTotalForMember($member);

        $allBadges = $this->badgeRepository->findAvailableForEvaluation();
        $newlyAwarded = [];

        foreach ($allBadges as $badge) {
            if ($this->memberBadgeRepository->hasBadge($member, $badge)) {
                continue;
            }

            $streakMet = ($badge->getRequiredStreak() > 0 && $currentStreak >= $badge->getRequiredStreak());
            $visitsMet = ($badge->getRequiredVisits() > 0 && $totalVisits >= $badge->getRequiredVisits());

            if ($streakMet || $visitsMet) {
                $memberBadge = new MemberBadge($member, $badge);
                $this->entityManager->persist($memberBadge);
                $newlyAwarded[] = $badge;
            }
        }

        if (!empty($newlyAwarded)) {
            $this->entityManager->flush();
        }

        return $newlyAwarded;
    }

    /**
     * Claim perk reward for an earned badge.
     */
    public function claimPerk(User $member, int $memberBadgeId): MemberBadge
    {
        $memberBadge = $this->memberBadgeRepository->find($memberBadgeId);
        if (!$memberBadge || $memberBadge->getMember()->getId() !== $member->getId()) {
            throw new LogicException('Achievement badge record not found.');
        }

        if ($memberBadge->isPerkClaimed()) {
            throw new LogicException('This perk reward has already been claimed.');
        }

        if (!$memberBadge->getBadge()->getPerkReward()) {
            throw new LogicException('This badge does not have a redeemable physical perk.');
        }

        $memberBadge->claimPerk();
        $this->entityManager->flush();

        return $memberBadge;
    }

    /**
     * Join an active community challenge.
     */
    public function joinChallenge(User $member, int $challengeId): ChallengeParticipant
    {
        $challenge = $this->challengeRepository->find($challengeId);
        if (!$challenge || $challenge->getStatus() !== 'ACTIVE') {
            throw new LogicException('Active challenge not found.');
        }

        $existing = $this->participantRepository->findByChallengeAndMember($challenge, $member);
        if ($existing) {
            return $existing;
        }

        $participant = new ChallengeParticipant($challenge, $member);
        $this->entityManager->persist($participant);
        $this->entityManager->flush();

        return $participant;
    }

    /**
     * Contribute progress to joined active challenges upon member activity.
     */
    public function recordContribution(User $member, int $amount = 1): void
    {
        $activeChallenges = $this->challengeRepository->findActiveChallenges();
        foreach ($activeChallenges as $challenge) {
            $participant = $this->participantRepository->findByChallengeAndMember($challenge, $member);
            if ($participant) {
                $participant->addContribution($amount);
                $challenge->addProgress($amount);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * Returns the global streaks and attendance leaderboard.
     */
    public function getLeaderboard(int $limit = 10): array
    {
        $streaks = $this->streakRepository->createQueryBuilder('s')
            ->join('s.member', 'm')
            ->addSelect('m')
            ->where('s.currentStreak > 0')
            ->orderBy('s.currentStreak', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $topStreaks = [];
        $rank = 1;
        foreach ($streaks as $streak) {
            $member = $streak->getMember();
            $topStreaks[] = [
                'rank' => $rank++,
                'memberId' => $member->getId(),
                'name' => $member->getFullName() ?? 'Member #' . $member->getId(),
                'currentStreak' => $streak->getCurrentStreak(),
                'lastActive' => $streak->getLastSuccessDate()?->format('Y-m-d'),
            ];
        }

        return [
            'streakLeaders' => $topStreaks,
        ];
    }
}

