<?php

namespace App\AIAdvisor\Controller\Admin;

use App\AIAdvisor\Service\RetentionEngineService;
use App\Auth\Repository\UserRepository;
use App\Shared\Service\EmailNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/retention', name: 'api_admin_retention_')]
#[IsGranted('ROLE_ADMIN')]
class RetentionController extends AbstractController
{
    public function __construct(
        private readonly RetentionEngineService $retentionService,
        private readonly UserRepository $userRepository,
        private readonly EmailNotificationService $emailService
    ) {
    }

    #[Route('/at-risk', name: 'at_risk', methods: ['GET'])]
    public function atRisk(): JsonResponse
    {
        return $this->json($this->retentionService->scanAtRiskMembers());
    }

    #[Route('/nudge/{memberId}', name: 'send_nudge', methods: ['POST'])]
    public function sendNudge(int $memberId): JsonResponse
    {
        $member = $this->userRepository->find($memberId);
        if (!$member) {
            return $this->json(['error' => 'Member not found.'], Response::HTTP_NOT_FOUND);
        }

        $subject = 'We miss you at GymUp! Here is a free guest pass for you 🏋️';
        $body = sprintf(
            "Hi %s,\n\nWe noticed you haven't visited GymUp in a few days. Consistency is key, and we're here to help you get back on track!\n\nShow this email at the reception desk to bring a friend for FREE on your next visit.\n\nKeep pushing!\nThe GymUp Team",
            $member->getFullName() ?? 'there'
        );

        $this->emailService->sendEmail($member->getEmail(), $subject, $body);

        return $this->json([
            'success' => true,
            'message' => sprintf('Re-engagement message sent to %s.', $member->getEmail()),
        ]);
    }
}

