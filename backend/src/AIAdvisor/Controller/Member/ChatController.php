<?php

namespace App\AIAdvisor\Controller\Member;

use App\AIAdvisor\Service\FitnessCoachChatService;
use App\Auth\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/chat', name: 'api_chat_')]
class ChatController extends AbstractController
{
    public function __construct(
        private readonly FitnessCoachChatService $chatService
    ) {
    }

    #[Route('/message', name: 'send_message', methods: ['POST'])]
    public function sendMessage(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $message = trim($payload['message'] ?? '');
        $sessionId = $payload['sessionId'] ?? null;

        if (empty($message)) {
            return $this->json(['error' => 'Message cannot be empty.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->chatService->chat($user, $message, $sessionId);
            return $this->json([
                'success' => true,
                'reply' => $result['reply'],
                'timestamp' => $result['timestamp'],
                'sessionId' => $result['sessionId'],
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Failed to generate advice: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/history', name: 'get_history', methods: ['GET'])]
    public function getHistory(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $sessionId = $request->query->get('sessionId');
        $messages = $this->chatService->getHistory($user, $sessionId);

        return $this->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    #[Route('/history', name: 'clear_history', methods: ['DELETE'])]
    public function clearHistory(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $sessionId = $request->query->get('sessionId');
        $success = $this->chatService->clearHistory($user, $sessionId);

        return $this->json([
            'success' => $success,
            'message' => 'Chat history cleared successfully.'
        ]);
    }

    #[Route('/quick-prompts', name: 'quick_prompts', methods: ['GET'])]
    public function getQuickPrompts(): JsonResponse
    {
        return $this->json([
            'categories' => [
                [
                    'category' => 'Nutrition & Diet',
                    'icon' => '🥗',
                    'prompts' => [
                        'Suggest a high-protein post-workout meal for muscle recovery.',
                        'What should I eat 1 hour before a heavy lifting session?',
                        'How do I calculate my daily protein and caloric needs for fat loss?',
                        'Give me 3 easy high-protein breakfast recipes without protein powder.',
                    ]
                ],
                [
                    'category' => 'Workouts & Training',
                    'icon' => '🏋️',
                    'prompts' => [
                        'Design a 4-day Upper / Lower workout split for hypertrophy.',
                        'What is progressive overload and how do I apply it to my lifts?',
                        'Give me a 20-minute bodyweight HIIT workout for fat burn.',
                        'How many rest days should I take between leg workouts?',
                    ]
                ],
                [
                    'category' => 'Hydration & Recovery',
                    'icon' => '💧',
                    'prompts' => [
                        'How much water should I drink on heavy training days?',
                        'What can I do to reduce intense muscle soreness (DOMS)?',
                        'How does sleep impact muscle growth and fat loss?',
                    ]
                ]
            ]
        ]);
    }
}

