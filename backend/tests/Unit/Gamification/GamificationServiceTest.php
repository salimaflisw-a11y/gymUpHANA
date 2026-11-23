<?php

namespace App\Tests\Unit\Gamification;

use App\Attendance\Repository\CheckInRepository;
use App\Attendance\Repository\StreakRepository;
use App\Attendance\Entity\Streak;
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
use App\Gamification\Service\GamificationService;
use App\Shared\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GamificationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject        $em;
    private BadgeRepository&MockObject               $badgeRepo;
    private MemberBadgeRepository&MockObject         $memberBadgeRepo;
    private CommunityChallengeRepository&MockObject  $challengeRepo;
    private ChallengeParticipantRepository&MockObject $participantRepo;
    private CheckInRepository&MockObject             $checkInRepo;
    private StreakRepository&MockObject               $streakRepo;
    private UserRepository&MockObject                $userRepo;
    private GamificationService                      $service;

    protected function setUp(): void
    {
        $this->em              = $this->createMock(EntityManagerInterface::class);
        $this->badgeRepo       = $this->createMock(BadgeRepository::class);
        $this->memberBadgeRepo = $this->createMock(MemberBadgeRepository::class);
        $this->challengeRepo   = $this->createMock(CommunityChallengeRepository::class);
        $this->participantRepo = $this->createMock(ChallengeParticipantRepository::class);
        $this->checkInRepo     = $this->createMock(CheckInRepository::class);
        $this->streakRepo      = $this->createMock(StreakRepository::class);
        $this->userRepo        = $this->createMock(UserRepository::class);

        $this->service = new GamificationService(
            $this->em,
            $this->badgeRepo,
            $this->memberBadgeRepo,
            $this->challengeRepo,
            $this->participantRepo,
            $this->checkInRepo,
            $this->streakRepo,
            $this->userRepo,
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        $u = new User();
        $u->setEmail('m@gym.com')->setPassword('x')->setRole(UserRole::MEMBER)->setStatus('ACTIVE');
        return $u;
    }

    private function makeBadge(string $code, int $streakReq, int $visitReq, ?string $perk = null): Badge
    {
        return new Badge(
            code: $code,
            name: "Badge {$code}",
            description: 'Test badge',
            icon: 'star',
            category: $streakReq > 0 ? 'STREAK' : 'ATTENDANCE',
            requiredStreak: $streakReq,
            requiredVisits: $visitReq,
            perkReward: $perk,
        );
    }

    // ── evaluateMemberAchievements ────────────────────────────────────────────

    public function testEvaluateAwardsBadgeWhenStreakMet(): void
    {
        $member = $this->makeUser();
        $badge  = $this->makeBadge('STREAK_7', 7, 0, 'Free smoothie');

        $streak = new Streak($member);
        $streak->setCurrentStreak(10); // qualifies for STREAK_7

        $this->badgeRepo->method('findAll')->willReturn([$badge]); // triggers seedDefaultBadges skip
        $this->badgeRepo->method('findAvailableForEvaluation')->willReturn([$badge]);
        $this->streakRepo->method('findByMember')->willReturn($streak);
        $this->checkInRepo->method('countTotalForMember')->willReturn(5);
        $this->memberBadgeRepo->method('hasBadge')->willReturn(false);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $awarded = $this->service->evaluateMemberAchievements($member);

        $this->assertCount(1, $awarded);
        $this->assertSame($badge, $awarded[0]);
    }

    public function testEvaluateAwardsBadgeWhenVisitsMet(): void
    {
        $member = $this->makeUser();
        $badge  = $this->makeBadge('VISITS_25', 0, 25);

        $streak = new Streak($member);
        $streak->setCurrentStreak(0);

        $this->badgeRepo->method('findAll')->willReturn([$badge]);
        $this->badgeRepo->method('findAvailableForEvaluation')->willReturn([$badge]);
        $this->streakRepo->method('findByMember')->willReturn($streak);
        $this->checkInRepo->method('countTotalForMember')->willReturn(30); // qualifies
        $this->memberBadgeRepo->method('hasBadge')->willReturn(false);

        $this->em->method('persist');
        $this->em->method('flush');

        $awarded = $this->service->evaluateMemberAchievements($member);

        $this->assertCount(1, $awarded);
    }

    public function testEvaluateSkipsBadgesAlreadyEarned(): void
    {
        $member = $this->makeUser();
        $badge  = $this->makeBadge('STREAK_3', 3, 0);

        $streak = new Streak($member);
        $streak->setCurrentStreak(5);

        $this->badgeRepo->method('findAll')->willReturn([$badge]);
        $this->badgeRepo->method('findAvailableForEvaluation')->willReturn([$badge]);
        $this->streakRepo->method('findByMember')->willReturn($streak);
        $this->checkInRepo->method('countTotalForMember')->willReturn(10);
        $this->memberBadgeRepo->method('hasBadge')->willReturn(true); // already has it

        $this->em->expects($this->never())->method('persist');

        $awarded = $this->service->evaluateMemberAchievements($member);

        $this->assertCount(0, $awarded);
    }

    public function testEvaluateReturnsEmptyWhenNotQualified(): void
    {
        $member = $this->makeUser();
        $badge  = $this->makeBadge('STREAK_30', 30, 0);

        $streak = new Streak($member);
        $streak->setCurrentStreak(5); // only 5, needs 30

        $this->badgeRepo->method('findAll')->willReturn([$badge]);
        $this->badgeRepo->method('findAvailableForEvaluation')->willReturn([$badge]);
        $this->streakRepo->method('findByMember')->willReturn($streak);
        $this->checkInRepo->method('countTotalForMember')->willReturn(5);
        $this->memberBadgeRepo->method('hasBadge')->willReturn(false);

        $awarded = $this->service->evaluateMemberAchievements($member);

        $this->assertCount(0, $awarded);
    }

    // ── claimPerk ─────────────────────────────────────────────────────────────

    public function testClaimPerkThrowsWhenAlreadyClaimed(): void
    {
        $member     = $this->makeUser();
        $badge      = $this->makeBadge('STREAK_7', 7, 0, 'Free smoothie');
        $memberBadge = new MemberBadge($member, $badge);
        $memberBadge->claimPerk();

        $this->memberBadgeRepo->method('find')->willReturn($memberBadge);

        // Need same identity for member id check
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('already been claimed');
        $this->service->claimPerk($member, 1);
    }

    public function testClaimPerkThrowsWhenNoPerkAvailable(): void
    {
        $member     = $this->makeUser();
        $badge      = $this->makeBadge('STREAK_3', 3, 0, null); // no perk
        $memberBadge = new MemberBadge($member, $badge);

        $this->memberBadgeRepo->method('find')->willReturn($memberBadge);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('does not have a redeemable');
        $this->service->claimPerk($member, 1);
    }

    public function testClaimPerkThrowsWhenBadgeNotFound(): void
    {
        $member = $this->makeUser();
        $this->memberBadgeRepo->method('find')->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->service->claimPerk($member, 999);
    }

    // ── joinChallenge ─────────────────────────────────────────────────────────

    public function testJoinChallengeThrowsWhenNotFound(): void
    {
        $member = $this->makeUser();
        $this->challengeRepo->method('find')->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->service->joinChallenge($member, 999);
    }

    public function testJoinChallengeReturnsExistingParticipantIfAlreadyJoined(): void
    {
        $member    = $this->makeUser();
        $challenge = $this->createMock(CommunityChallenge::class);
        $challenge->method('getStatus')->willReturn('ACTIVE');
        $existing  = $this->createMock(ChallengeParticipant::class);

        $this->challengeRepo->method('find')->willReturn($challenge);
        $this->participantRepo->method('findByChallengeAndMember')->willReturn($existing);

        $result = $this->service->joinChallenge($member, 1);

        $this->assertSame($existing, $result);
    }
}
