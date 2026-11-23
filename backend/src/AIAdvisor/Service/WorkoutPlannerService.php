<?php

namespace App\AIAdvisor\Service;

use App\Equipment\Repository\EquipmentRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class WorkoutPlannerService
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly GeminiService $geminiService,
        private readonly EquipmentRepository $equipmentRepository,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Generate a structured workout routine using Gemini based on goals, level, and GymUp's equipment catalog.
     *
     * @return array<string, mixed>
     */
    public function generateWorkoutPlan(
        string $goal,
        string $targetMuscle,
        int $durationMinutes,
        string $fitnessLevel = 'INTERMEDIATE'
    ): array {
        // Fetch all available gym equipment
        $equipmentList = $this->equipmentRepository->findAll();
        $catalogSummary = array_map(function ($eq) {
            return [
                'id' => $eq->getId(),
                'name' => $eq->getName(),
                'category' => $eq->getCategory(),
                'targetMuscles' => $eq->getTargetMuscles(),
            ];
        }, $equipmentList);

        $systemPrompt = <<<PROMPT
You are GymUp's Elite Head Strength & Conditioning Coach.
Generate an optimal, science-based training routine for a member.
CRITICAL RULE: You MUST return ONLY valid, raw JSON (no markdown formatting, no ```json ``` fences, just plain JSON).

The JSON structure must match this schema exactly:
{
  "title": "String (e.g. Explosive Upper Body Hypertrophy)",
  "goal": "String",
  "targetMuscle": "String",
  "estimatedDurationMinutes": Integer,
  "difficulty": "String",
  "warmup": [
    { "name": "String", "duration": "String", "notes": "String" }
  ],
  "exercises": [
    {
      "name": "String (e.g. Flat Barbell Bench Press)",
      "equipmentId": Integer or null,
      "equipmentName": "String",
      "targetMuscle": "String",
      "sets": Integer,
      "reps": "String (e.g. 8-10 or 12)",
      "restSeconds": Integer,
      "coachingTip": "String",
      "rpe": "String (e.g. 8/10)"
    }
  ],
  "cooldown": [
    { "name": "String", "duration": "String", "notes": "String" }
  ],
  "coachAdvice": "String"
}

Available gym equipment at GymUp:
PROMPT;

        $systemPrompt .= "\n" . json_encode($catalogSummary);

        $userMessage = "Create a {$durationMinutes}-minute {$fitnessLevel} level workout focusing on {$targetMuscle} with the primary goal of {$goal}. Select from our available equipment when applicable.";

        try {
            $rawResponse = $this->geminiService->generateResponse($systemPrompt, [], $userMessage);

            // Clean markdown blocks if Gemini includes them
            $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawResponse));
            $decoded = json_decode($cleanJson, true);

            if (is_array($decoded) && isset($decoded['exercises'])) {
                return $decoded;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Gemini workout plan generation fallback: ' . $e->getMessage());
        }

        // High quality deterministic fallback if API is unreachable or rate-limited
        return $this->getFallbackPlan($goal, $targetMuscle, $durationMinutes, $fitnessLevel, $catalogSummary);
    }

    private function getFallbackPlan(string $goal, string $targetMuscle, int $duration, string $level, array $catalog): array
    {
        $isLegs = stripos($targetMuscle, 'leg') !== false || stripos($targetMuscle, 'quad') !== false;
        $isBack = stripos($targetMuscle, 'back') !== false || stripos($targetMuscle, 'pull') !== false;

        if ($isLegs) {
            return [
                'title' => 'High-Tension Quad & Glute Hypertrophy',
                'goal' => $goal,
                'targetMuscle' => $targetMuscle,
                'estimatedDurationMinutes' => $duration,
                'difficulty' => $level,
                'warmup' => [
                    ['name' => 'AirBike Warmup', 'duration' => '5 mins', 'notes' => 'Low resistance cadence to elevate core body temperature'],
                    ['name' => 'Hip & Ankle Mobility', 'duration' => '3 mins', 'notes' => 'Deep bodyweight squats with 3s pause at bottom']
                ],
                'exercises' => [
                    [
                        'name' => 'Barbell Back Squat',
                        'equipmentId' => 2,
                        'equipmentName' => 'Power Squat Rack',
                        'targetMuscle' => 'Quadriceps & Glutes',
                        'sets' => 4,
                        'reps' => '6-8',
                        'restSeconds' => 120,
                        'coachingTip' => 'Brace core hard, hit parallel depth, drive through midfoot.',
                        'rpe' => '8.5/10'
                    ],
                    [
                        'name' => '45-Degree Incline Leg Press',
                        'equipmentId' => 5,
                        'equipmentName' => '45-Degree Incline Leg Press',
                        'targetMuscle' => 'Quadriceps & Calves',
                        'sets' => 3,
                        'reps' => '10-12',
                        'restSeconds' => 90,
                        'coachingTip' => 'Full range of motion, avoid locking out knees at top.',
                        'rpe' => '8/10'
                    ]
                ],
                'cooldown' => [
                    ['name' => 'Hamstring & Hip Flexor Static Stretch', 'duration' => '4 mins', 'notes' => 'Hold each stretch for 45s']
                ],
                'coachAdvice' => 'Focus on controlled 3-second eccentrics to maximize mechanical tension.'
            ];
        }

        if ($isBack) {
            return [
                'title' => 'V-Taper Lat & Upper Back Developer',
                'goal' => $goal,
                'targetMuscle' => $targetMuscle,
                'estimatedDurationMinutes' => $duration,
                'difficulty' => $level,
                'warmup' => [
                    ['name' => 'Concept2 Rower Steady', 'duration' => '5 mins', 'notes' => 'Light 22 SPM pace to open shoulder girdle']
                ],
                'exercises' => [
                    [
                        'name' => 'Wide-Grip Lat Pulldown',
                        'equipmentId' => 4,
                        'equipmentName' => 'Selectorized Lat Pulldown',
                        'targetMuscle' => 'Latissimus Dorsi',
                        'sets' => 4,
                        'reps' => '8-10',
                        'restSeconds' => 75,
                        'coachingTip' => 'Pull elbows down toward back pockets, squeeze lats at clavicle.',
                        'rpe' => '8/10'
                    ],
                    [
                        'name' => 'Low Cable Seated Row',
                        'equipmentId' => 8,
                        'equipmentName' => 'Low Cable Seated Row',
                        'targetMuscle' => 'Middle Back & Rhomboids',
                        'sets' => 3,
                        'reps' => '10-12',
                        'restSeconds' => 60,
                        'coachingTip' => 'Maintain neutral spine, retract scapulae before bending elbows.',
                        'rpe' => '8/10'
                    ]
                ],
                'cooldown' => [
                    ['name' => 'Lat Hang & Thoracic Rotations', 'duration' => '3 mins', 'notes' => 'Decompress spine hanging from pullup bar']
                ],
                'coachAdvice' => 'Initiate every pull from your back, not by gripping too hard with your forearms.'
            ];
        }

        // Default Chest / Push
        return [
            'title' => 'Plate-Loaded Chest & Triceps Dominance',
            'goal' => $goal,
            'targetMuscle' => $targetMuscle,
            'estimatedDurationMinutes' => $duration,
            'difficulty' => $level,
            'warmup' => [
                ['name' => 'Rotator Cuff Band Warmup', 'duration' => '4 mins', 'notes' => 'Internal and external rotations at light tension']
            ],
            'exercises' => [
                [
                    'name' => 'Olympic Flat Bench Press',
                    'equipmentId' => 1,
                    'equipmentName' => 'Olympic Flat Bench Press',
                    'targetMuscle' => 'Chest & Triceps',
                    'sets' => 4,
                    'reps' => '6-8',
                    'restSeconds' => 90,
                    'coachingTip' => 'Retract shoulder blades, slight arch in upper back, press in slight J-curve.',
                    'rpe' => '8.5/10'
                ],
                [
                    'name' => 'Cable Chest Flyes',
                    'equipmentId' => 3,
                    'equipmentName' => 'Dual Adjustable Pulley Cable Machine',
                    'targetMuscle' => 'Pectoralis Major',
                    'sets' => 3,
                    'reps' => '12-15',
                    'restSeconds' => 60,
                    'coachingTip' => 'Focus on peak squeeze at center, 2-second eccentric stretch.',
                    'rpe' => '8/10'
                ]
            ],
            'cooldown' => [
                ['name' => 'Doorway Pec Stretch', 'duration' => '3 mins', 'notes' => 'Breathe deeply into diaphragm']
            ],
            'coachAdvice' => 'Keep your shoulders pinned back against the bench to protect the anterior delts.'
        ];
    }
}
