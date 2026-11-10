<?php

namespace App\Gamification\Controller\Admin;

use App\Gamification\Entity\CommunityChallenge;
use App\Gamification\Repository\CommunityChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/gamification/challenges', name: 'api_admin_gamification_challenges_')]
#[IsGranted('ROLE_ADMIN')]
class ChallengeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommunityChallengeRepository $challengeRepository
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $challenges = $this->challengeRepository->findBy([], ['startDate' => 'DESC']);
        $data = array_map(function ($c) {
            return [
                'id' => $c->getId(),
                'title' => $c->getTitle(),
                'status' => $c->getStatus(),
                'currentProgress' => $c->getCurrentProgress(),
                'targetGoal' => $c->getTargetGoal(),
                'progressPercentage' => $c->getProgressPercentage(),
                'startDate' => $c->getStartDate()->format('Y-m-d'),
                'endDate' => $c->getEndDate()->format('Y-m-d'),
            ];
        }, $challenges);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $targetGoal = (int) ($data['targetGoal'] ?? 0);
        $metric = $data['metric'] ?? 'TOTAL_VISITS';
        $reward = $data['rewardDescription'] ?? null;
        $startDateStr = $data['startDate'] ?? 'today';
        $endDateStr = $data['endDate'] ?? '+30 days';

        if (!$title || !$description || $targetGoal <= 0) {
            return $this->json(['error' => 'Title, description, and positive target goal are required.'], Response::HTTP_BAD_REQUEST);
        }

        $startDate = new \DateTimeImmutable($startDateStr);
        $endDate = new \DateTimeImmutable($endDateStr);

        $challenge = new CommunityChallenge(
            title: $title,
            description: $description,
            targetGoal: $targetGoal,
            startDate: $startDate,
            endDate: $endDate,
            goalMetric: $metric,
            rewardDescription: $reward
        );

        $this->entityManager->persist($challenge);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Challenge created successfully',
            'id' => $challenge->getId(),
        ], Response::HTTP_CREATED);
    }
}

