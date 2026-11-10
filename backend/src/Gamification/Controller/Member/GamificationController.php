<?php

namespace App\Gamification\Controller\Member;

use App\Auth\Entity\User;
use App\Gamification\Repository\BadgeRepository;
use App\Gamification\Repository\CommunityChallengeRepository;
use App\Gamification\Repository\MemberBadgeRepository;
use App\Gamification\Service\GamificationService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/gamification', name: 'api_gamification_')]
class GamificationController extends AbstractController
{
    public function __construct(
        private readonly GamificationService $gamificationService,
        private readonly BadgeRepository $badgeRepository,
        private readonly MemberBadgeRepository $memberBadgeRepository,
        private readonly CommunityChallengeRepository $challengeRepository
    ) {
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(#[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Auto evaluate any earned badges
        $newBadges = $this->gamificationService->evaluateMemberAchievements($member);
        $earnedBadges = $this->memberBadgeRepository->findByMember($member);
        $allBadges = $this->badgeRepository->findAll();

        $badgesData = array_map(function ($mb) {
            $b = $mb->getBadge();
            return [
                'memberBadgeId' => $mb->getId(),
                'badgeId' => $b->getId(),
                'code' => $b->getCode(),
                'name' => $b->getName(),
                'description' => $b->getDescription(),
                'icon' => $b->getIcon(),
                'category' => $b->getCategory(),
                'unlockedAt' => $mb->getUnlockedAt()->format(\DateTimeInterface::ATOM),
                'perkReward' => $b->getPerkReward(),
                'isPerkClaimed' => $mb->isPerkClaimed(),
                'claimedAt' => $mb->getClaimedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }, $earnedBadges);

        return $this->json([
            'memberId' => $member->getId(),
            'totalBadgesUnlocked' => count($earnedBadges),
            'totalBadgesAvailable' => count($allBadges),
            'newlyAwarded' => array_map(fn($b) => $b->getName(), $newBadges),
            'badges' => $badgesData,
        ]);
    }

    #[Route('/badges/claim/{memberBadgeId}', name: 'claim_perk', methods: ['POST'])]
    public function claimPerk(int $memberBadgeId, #[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $mb = $this->gamificationService->claimPerk($member, $memberBadgeId);
            return $this->json([
                'success' => true,
                'message' => 'Perk successfully claimed! Show this confirmation to reception.',
                'perk' => $mb->getBadge()->getPerkReward(),
                'claimedAt' => $mb->getClaimedAt()->format(\DateTimeInterface::ATOM),
            ]);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/challenges', name: 'challenges', methods: ['GET'])]
    public function challenges(#[CurrentUser] ?User $member): JsonResponse
    {
        $this->gamificationService->seedDefaultChallenges();
        $challenges = $this->challengeRepository->findActiveChallenges();

        $data = array_map(function ($c) use ($member) {
            $isParticipant = false;
            $myContribution = 0;

            if ($member) {
                foreach ($c->getParticipants() as $p) {
                    if ($p->getMember()->getId() === $member->getId()) {
                        $isParticipant = true;
                        $myContribution = $p->getContributedAmount();
                        break;
                    }
                }
            }

            return [
                'id' => $c->getId(),
                'title' => $c->getTitle(),
                'description' => $c->getDescription(),
                'metric' => $c->getGoalMetric(),
                'targetGoal' => $c->getTargetGoal(),
                'currentProgress' => $c->getCurrentProgress(),
                'progressPercentage' => $c->getProgressPercentage(),
                'startDate' => $c->getStartDate()->format('Y-m-d'),
                'endDate' => $c->getEndDate()->format('Y-m-d'),
                'reward' => $c->getRewardDescription(),
                'isJoined' => $isParticipant,
                'myContribution' => $myContribution,
                'participantCount' => $c->getParticipants()->count(),
            ];
        }, $challenges);

        return $this->json($data);
    }

    #[Route('/challenges/{id}/join', name: 'join_challenge', methods: ['POST'])]
    public function joinChallenge(int $id, #[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $participant = $this->gamificationService->joinChallenge($member, $id);
            return $this->json([
                'success' => true,
                'message' => 'You have joined the community challenge!',
                'challenge' => $participant->getChallenge()->getTitle(),
            ]);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/leaderboard', name: 'leaderboard', methods: ['GET'])]
    public function leaderboard(): JsonResponse
    {
        return $this->json($this->gamificationService->getLeaderboard());
    }
}

