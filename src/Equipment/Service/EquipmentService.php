<?php

namespace App\Equipment\Service;

use App\Auth\Entity\User;
use App\Equipment\Entity\Equipment;
use App\Equipment\Entity\EquipmentLog;
use App\Equipment\Repository\EquipmentLogRepository;
use App\Equipment\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

class EquipmentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EquipmentRepository $equipmentRepository,
        private readonly EquipmentLogRepository $equipmentLogRepository
    ) {
    }

    /**
     * Seeds initial gym machine inventory with video guides and target muscles.
     */
    public function seedDefaultEquipment(): void
    {
        if (!empty($this->equipmentRepository->findAll())) {
            return;
        }

        $items = [
            [
                'qrCode' => 'GYM-CHEST-01',
                'name' => 'Plate-Loaded Incline Chest Press',
                'category' => 'CHEST',
                'targetMuscles' => ['Upper Pectoralis', 'Anterior Deltoid', 'Triceps'],
                'setupInstructions' => 'Adjust the seat so the handles align directly with the top of your chest. Plant feet flat on the floor, retract scapula, and press smoothly without locking elbows.',
                'videoTutorialUrl' => 'https://assets.gymup.com/videos/incline-press.mp4',
                'imageUrl' => 'https://assets.gymup.com/images/incline-press.jpg',
                'modelNumber' => 'Hammer-Strength-ISO-01',
            ],
            [
                'qrCode' => 'GYM-BACK-01',
                'name' => 'Dual-Pulley Lat Pulldown',
                'category' => 'BACK',
                'targetMuscles' => ['Latissimus Dorsi', 'Teres Major', 'Biceps Brachii', 'Rhomboids'],
                'setupInstructions' => 'Secure thigh pads firmly against your upper legs. Grasp the bar with a wide grip, lean back slightly (10-15 degrees), and pull toward upper chest driving elbows down.',
                'videoTutorialUrl' => 'https://assets.gymup.com/videos/lat-pulldown.mp4',
                'imageUrl' => 'https://assets.gymup.com/images/lat-pulldown.jpg',
                'modelNumber' => 'LifeFitness-Pro2-Lat',
            ],
            [
                'qrCode' => 'GYM-LEG-01',
                'name' => 'Linear Hack Squat 45°',
                'category' => 'LEGS',
                'targetMuscles' => ['Quadriceps', 'Gluteus Maximus', 'Adductors'],
                'setupInstructions' => 'Position back flat against pad, shoulders locked under pads. Place feet shoulder-width apart in middle of platform. Disengage safety catches, descend to 90 degrees knee flexion, drive up through mid-foot.',
                'videoTutorialUrl' => 'https://assets.gymup.com/videos/hack-squat.mp4',
                'imageUrl' => 'https://assets.gymup.com/images/hack-squat.jpg',
                'modelNumber' => 'Cybex-Squat-45',
            ],
            [
                'qrCode' => 'GYM-LEG-02',
                'name' => 'Seated Leg Curl Machine',
                'category' => 'LEGS',
                'targetMuscles' => ['Hamstrings (Biceps Femoris)', 'Gastrocnemius'],
                'setupInstructions' => 'Align knee joints with the machine pivot axis. Adjust back pad so your back is fully supported. Lock top thigh pad down firmly before curling.',
                'videoTutorialUrl' => 'https://assets.gymup.com/videos/seated-leg-curl.mp4',
                'imageUrl' => 'https://assets.gymup.com/images/seated-leg-curl.jpg',
                'modelNumber' => 'Technogym-Selection-900',
            ],
            [
                'qrCode' => 'GYM-SHOULDER-01',
                'name' => 'Iso-Lateral Shoulder Overhead Press',
                'category' => 'SHOULDERS',
                'targetMuscles' => ['Anterior Deltoid', 'Lateral Deltoid', 'Triceps', 'Upper Trapezius'],
                'setupInstructions' => 'Adjust seat so handles are at ear height. Brace core, maintain neutral spine, and push upward in an arc.',
                'videoTutorialUrl' => 'https://assets.gymup.com/videos/shoulder-press.mp4',
                'imageUrl' => 'https://assets.gymup.com/images/shoulder-press.jpg',
                'modelNumber' => 'Hammer-Strength-OHP',
            ],
            [
                'qrCode' => 'GYM-CABLE-01',
                'name' => 'Functional Cable Crossover Tower',
                'category' => 'ARMS',
                'targetMuscles' => ['Biceps', 'Triceps', 'Chest', 'Rear Delts', 'Core'],
                'setupInstructions' => 'Select attachment (rope, D-handles, straight bar). Adjust pulley height to target angle. Always ensure pin is securely engaged before lifting.',
                'videoTutorialUrl' => 'https://assets.gymup.com/videos/cable-tower.mp4',
                'imageUrl' => 'https://assets.gymup.com/images/cable-tower.jpg',
                'modelNumber' => 'Freemotion-Dual-Cable',
            ],
        ];

        foreach ($items as $item) {
            $equipment = new Equipment(
                qrCode: $item['qrCode'],
                name: $item['name'],
                category: $item['category'],
                targetMuscles: $item['targetMuscles'],
                setupInstructions: $item['setupInstructions'],
                videoTutorialUrl: $item['videoTutorialUrl'],
                imageUrl: $item['imageUrl'],
                modelNumber: $item['modelNumber']
            );
            $this->entityManager->persist($equipment);
        }

        $this->entityManager->flush();
    }

    /**
     * Resolves QR scan on gym floor: returns machine info, tutorial video, personal history & PR.
     */
    public function scanQr(string $qrCode, User $member): array
    {
        $this->seedDefaultEquipment();

        $equipment = $this->equipmentRepository->findByQrCode($qrCode);
        if (!$equipment) {
            throw new LogicException(sprintf('No gym machine found with QR identifier "%s".', $qrCode));
        }

        $logs = $this->equipmentLogRepository->findByMemberAndEquipment($member, $equipment, 5);
        $highest1Rm = $this->equipmentLogRepository->findHighest1Rm($member, $equipment);

        $lastSeatSetting = null;
        if (!empty($logs)) {
            $lastSeatSetting = $logs[0]->getSeatSetting();
        }

        $recentLogs = array_map(function ($l) {
            return [
                'id' => $l->getId(),
                'weightKg' => $l->getWeightKg(),
                'reps' => $l->getReps(),
                'sets' => $l->getSets(),
                'seatSetting' => $l->getSeatSetting(),
                'estimated1Rm' => $l->getEstimated1Rm(),
                'isPR' => $l->isPersonalRecord(),
                'loggedAt' => $l->getLoggedAt()->format('Y-m-d H:i'),
            ];
        }, $logs);

        return [
            'equipment' => [
                'id' => $equipment->getId(),
                'qrCode' => $equipment->getQrCode(),
                'name' => $equipment->getName(),
                'category' => $equipment->getCategory(),
                'targetMuscles' => $equipment->getTargetMuscles(),
                'instructions' => $equipment->getSetupInstructions(),
                'videoTutorial' => $equipment->getVideoTutorialUrl(),
                'imageUrl' => $equipment->getImageUrl(),
                'modelNumber' => $equipment->getModelNumber(),
            ],
            'memberHistory' => [
                'totalSetsLogged' => count($logs),
                'personalRecord1Rm' => $highest1Rm,
                'lastKnownSeatSetting' => $lastSeatSetting,
                'recentLogs' => $recentLogs,
            ]
        ];
    }

    /**
     * Logs a workout set and automatically checks for a new PR.
     */
    public function logSet(
        User $member,
        int $equipmentId,
        float $weightKg,
        int $reps,
        int $sets = 1,
        ?string $seatSetting = null,
        ?string $notes = null
    ): EquipmentLog {
        $equipment = $this->equipmentRepository->find($equipmentId);
        if (!$equipment) {
            throw new LogicException('Equipment not found.');
        }

        $previousBest1Rm = $this->equipmentLogRepository->findHighest1Rm($member, $equipment);

        $log = new EquipmentLog(
            member: $member,
            equipment: $equipment,
            weightKg: $weightKg,
            reps: $reps,
            sets: $sets,
            seatSetting: $seatSetting,
            notes: $notes
        );

        // Check if this set sets a new Personal Record
        if ($previousBest1Rm === null || $log->getEstimated1Rm() > $previousBest1Rm) {
            $log->setPersonalRecord(true);
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }

    /**
     * Retrieves the member's complete PR Vault across all gym machines.
     */
    public function getMemberPrVault(User $member): array
    {
        $prLogs = $this->equipmentLogRepository->findMemberPersonalRecords($member);

        $vault = [];
        foreach ($prLogs as $pr) {
            $eq = $pr->getEquipment();
            $category = $eq->getCategory();

            if (!isset($vault[$category])) {
                $vault[$category] = [];
            }

            $vault[$category][] = [
                'equipmentName' => $eq->getName(),
                'category' => $category,
                'weightKg' => $pr->getWeightKg(),
                'reps' => $pr->getReps(),
                'estimated1Rm' => $pr->getEstimated1Rm(),
                'seatSetting' => $pr->getSeatSetting(),
                'achievedAt' => $pr->getLoggedAt()->format('Y-m-d'),
            ];
        }

        return $vault;
    }
}

