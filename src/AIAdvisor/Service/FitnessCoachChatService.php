<?php

namespace App\AIAdvisor\Service;

use App\Attendance\Entity\CheckIn;
use App\Attendance\Entity\Streak;
use App\Auth\Entity\User;
use App\Booking\Entity\Booking;
use App\Membership\Entity\Subscription;
use App\Shared\Service\FirebaseService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FitnessCoachChatService
{
    private const COLLECTION_NAME = 'chat_history';
    private LoggerInterface $logger;

    public function __construct(
        private readonly GeminiService $geminiService,
        private readonly FirebaseService $firebaseService,
        private readonly EntityManagerInterface $em,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Send a message and get an AI response grounded in the member's gym context.
     *
     * @return array{reply: string, timestamp: string, sessionId: string}
     */
    public function chat(?User $user, string $userMessage, ?string $sessionId = null): array
    {
        $userId = $user ? (string) $user->getId() : ($sessionId ?: 'guest_' . substr(md5($userMessage . time()), 0, 10));
        $now = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        // 1. Fetch conversation history from Firestore
        $historyDoc = $this->firebaseService->getDocument(self::COLLECTION_NAME, $userId);
        $rawMessages = $historyDoc['messages'] ?? [];

        // Format history for Gemini (last 10 turns to keep prompt efficient and fast)
        $formattedHistory = [];
        $recentRaw = array_slice($rawMessages, -10);
        foreach ($recentRaw as $msg) {
            $formattedHistory[] = [
                'role' => $msg['sender'] === 'user' ? 'user' : 'model',
                'text' => $msg['text'] ?? ''
            ];
        }

        // 2. Build personalized system prompt
        $systemPrompt = $this->buildSystemPrompt($user);

        // 3. Call Gemini
        $aiReply = $this->geminiService->generateResponse($systemPrompt, $formattedHistory, $userMessage);

        // 4. Update and persist chat history in Firestore
        $newMessages = $rawMessages;
        $newMessages[] = [
            'id' => uniqid('msg_u_'),
            'sender' => 'user',
            'text' => $userMessage,
            'timestamp' => $now,
        ];
        $newMessages[] = [
            'id' => uniqid('msg_a_'),
            'sender' => 'assistant',
            'text' => $aiReply,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        // Keep maximum 40 messages stored per user to prevent document bloating
        if (count($newMessages) > 40) {
            $newMessages = array_slice($newMessages, -40);
        }

        $this->firebaseService->setDocument(self::COLLECTION_NAME, $userId, [
            'userId' => $userId,
            'userName' => $user ? ($user->getFullName() ?: $user->getEmail()) : 'Guest Client',
            'updatedAt' => $now,
            'messages' => $newMessages,
        ], true);

        return [
            'reply' => $aiReply,
            'timestamp' => $now,
            'sessionId' => $userId,
        ];
    }

    /**
     * Retrieve chat history for the user or session.
     */
    public function getHistory(?User $user, ?string $sessionId = null): array
    {
        $userId = $user ? (string) $user->getId() : ($sessionId ?: '');
        if (!$userId) {
            return [];
        }

        $doc = $this->firebaseService->getDocument(self::COLLECTION_NAME, $userId);
        return $doc['messages'] ?? [];
    }

    /**
     * Clear chat history for the user or session.
     */
    public function clearHistory(?User $user, ?string $sessionId = null): bool
    {
        $userId = $user ? (string) $user->getId() : ($sessionId ?: '');
        if (!$userId) {
            return false;
        }

        return $this->firebaseService->deleteDocument(self::COLLECTION_NAME, $userId);
    }

    /**
     * Construct the personalized system prompt.
     */
    private function buildSystemPrompt(?User $user): string
    {
        $prompt = <<<EOT
You are the GymUp AI Master Coach & Sports Nutritionist, an elite, friendly, and science-grounded personal trainer and dietician for the GymUp Fitness & Wellness platform.

YOUR CORE EXPERTISE:
1. Training & Workout Programming:
   - Progressive overload, optimal sets/reps, exercise technique cues, hypertrophy, strength, endurance, and functional mobility.
   - Tailored workout splits (Push/Pull/Legs, Upper/Lower, Full Body, HIIT).
2. Nutrition, Diet & Meal Planning:
   - Macronutrient distribution (protein target ~1.6-2.2g per kg of bodyweight, healthy fats, complex carbs).
   - Pre-workout fueling and post-workout muscle protein synthesis window meals.
   - Caloric balance: safe caloric deficit for fat loss, clean surplus for lean muscle mass, maintenance targets.
   - Hydration strategies, electrolyte replenishment, and recovery sleep.
3. Recovery & Injury Prevention:
   - Rest days, deload weeks, active recovery, foam rolling, dynamic stretching.

COMMUNICATION STYLE:
- Energetic, encouraging, concise, and structured.
- Use markdown formatting: bold key tips, bullet points for recipes/exercise steps, and clear headings.
- Always be practical and give realistic food items and meal ideas that are easy to prepare.
- When asked for a meal plan or macro calculation, ask for their height, weight, gender, and activity level if not provided, or provide a standard baseline with clear instructions.
- SAFETY DISCLAIMER: If asked about medical diagnoses, eating disorders, or pharmaceuticals/anabolic steroids, clarify that you provide general sports science guidance and advise consulting a medical doctor or registered clinical dietitian.

EOT;

        if ($user) {
            $contextDetails = $this->gatherMemberContext($user);
            $prompt .= "\nCURRENT MEMBER CONTEXT (Use this to personalize your answers naturally):\n";
            $prompt .= "- Name: " . ($user->getFullName() ?: 'Member') . "\n";
            $prompt .= "- Account Email: " . $user->getEmail() . "\n";
            $prompt .= "- Role: " . $user->getRole()->value . "\n";

            if (!empty($contextDetails['streak'])) {
                $prompt .= "- Workout Streak: " . $contextDetails['streak']['current'] . " days active (Record: " . $contextDetails['streak']['longest'] . " days)\n";
            }
            if (!empty($contextDetails['subscription'])) {
                $prompt .= "- Membership Plan: " . $contextDetails['subscription']['planName'] . " (" . $contextDetails['subscription']['status'] . ")\n";
            }
            if (!empty($contextDetails['recentCheckInsCount'])) {
                $prompt .= "- Recent Gym Check-Ins: " . $contextDetails['recentCheckInsCount'] . " visits recorded recently\n";
            }
            if (!empty($contextDetails['recentClasses'])) {
                $prompt .= "- Recent Booked Classes: " . implode(', ', $contextDetails['recentClasses']) . "\n";
            }
            $prompt .= "\nAddress the member warmly, acknowledge their streak/progress when appropriate, and motivate them to reach their peak potential!";
        } else {
            $prompt .= "\nThe user is currently browsing as a Guest client. Provide welcoming, elite-level fitness, nutrition, and workout advice, and highlight how GymUp classes and coaching can accelerate their progress.";
        }

        return $prompt;
    }

    /**
     * Gather gym metrics from database with safety fallbacks.
     */
    private function gatherMemberContext(User $user): array
    {
        $context = [
            'streak' => null,
            'subscription' => null,
            'recentCheckInsCount' => 0,
            'recentClasses' => [],
        ];

        try {
            // 1. Streak
            $streakRepo = $this->em->getRepository(Streak::class);
            $streak = $streakRepo->findOneBy(['member' => $user]);
            if ($streak) {
                $context['streak'] = [
                    'current' => $streak->getCurrentStreak(),
                    'longest' => $streak->getLongestStreak(),
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->debug('Error loading streak context: ' . $e->getMessage());
        }

        try {
            // 2. Active Subscription
            $subRepo = $this->em->getRepository(Subscription::class);
            $sub = $subRepo->findOneBy(['member' => $user, 'status' => 'ACTIVE']);
            if ($sub && $sub->getPlan()) {
                $context['subscription'] = [
                    'planName' => $sub->getPlan()->getName(),
                    'status' => $sub->getStatus(),
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->debug('Error loading subscription context: ' . $e->getMessage());
        }

        try {
            // 3. Recent CheckIns
            $checkInRepo = $this->em->getRepository(CheckIn::class);
            $checkIns = $checkInRepo->findBy(['member' => $user], ['checkedInAt' => 'DESC'], 5);
            $context['recentCheckInsCount'] = count($checkIns);
        } catch (\Throwable $e) {
            $this->logger->debug('Error loading checkin context: ' . $e->getMessage());
        }

        try {
            // 4. Bookings
            $bookingRepo = $this->em->getRepository(Booking::class);
            $bookings = $bookingRepo->findBy(['member' => $user], ['bookedAt' => 'DESC'], 3);
            foreach ($bookings as $b) {
                if ($b->getClassSession()) {
                    $context['recentClasses'][] = $b->getClassSession()->getTitle();
                }
            }
        } catch (\Throwable $e) {
            $this->logger->debug('Error loading booking context: ' . $e->getMessage());
        }

        return $context;
    }
}

