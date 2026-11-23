<?php

namespace App\AIAdvisor\Controller\Member;

use App\AIAdvisor\Service\RecoveryAdvisorService;
use App\AIAdvisor\Service\WorkoutPlannerService;
use App\Auth\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/advisor', name: 'api_advisor_')]
class AdvisorController extends AbstractController
{
    public function __construct(
        private readonly RecoveryAdvisorService $recoveryService,
        private readonly WorkoutPlannerService $workoutPlannerService
    ) {
    }

    #[Route('/recovery', name: 'recovery', methods: ['GET'])]
    public function recovery(#[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $assessment = $this->recoveryService->calculateReadiness($member);
        return $this->json($assessment);
    }

    #[Route('/generate-workout', name: 'generate_workout', methods: ['POST'])]
    public function generateWorkout(Request $request, #[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $goal = (string) ($data['goal'] ?? 'Hypertrophy & Muscle Gain');
        $targetMuscle = (string) ($data['targetMuscle'] ?? 'Chest & Triceps');
        $duration = (int) ($data['durationMinutes'] ?? 45);
        $level = (string) ($data['fitnessLevel'] ?? 'INTERMEDIATE');

        $plan = $this->workoutPlannerService->generateWorkoutPlan($goal, $targetMuscle, $duration, $level);

        return $this->json($plan);
    }
}


