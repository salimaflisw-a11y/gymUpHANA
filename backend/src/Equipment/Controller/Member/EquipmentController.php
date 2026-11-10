<?php

namespace App\Equipment\Controller\Member;

use App\Auth\Entity\User;
use App\Equipment\Repository\EquipmentRepository;
use App\Equipment\Service\EquipmentService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/equipment', name: 'api_equipment_')]
class EquipmentController extends AbstractController
{
    public function __construct(
        private readonly EquipmentService $equipmentService,
        private readonly EquipmentRepository $equipmentRepository
    ) {
    }

    #[Route('/scan/{qrCode}', name: 'scan_qr', methods: ['GET'])]
    public function scan(string $qrCode, #[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $data = $this->equipmentService->scanQr($qrCode, $member);
            return $this->json($data);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/log', name: 'log_set', methods: ['POST'])]
    public function log(Request $request, #[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $equipmentId = (int) ($data['equipmentId'] ?? 0);
        $weightKg = (float) ($data['weightKg'] ?? 0);
        $reps = (int) ($data['reps'] ?? 0);
        $sets = (int) ($data['sets'] ?? 1);
        $seatSetting = $data['seatSetting'] ?? null;
        $notes = $data['notes'] ?? null;

        if ($equipmentId <= 0 || $weightKg <= 0 || $reps <= 0) {
            return $this->json(['error' => 'Valid equipmentId, weightKg (> 0), and reps (> 0) are required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $log = $this->equipmentService->logSet(
                member: $member,
                equipmentId: $equipmentId,
                weightKg: $weightKg,
                reps: $reps,
                sets: $sets,
                seatSetting: $seatSetting,
                notes: $notes
            );

            return $this->json([
                'success' => true,
                'message' => $log->isPersonalRecord()
                    ? '🎉 BOOM! New Personal Record (PR) achieved and logged to your vault!'
                    : 'Workout set successfully logged.',
                'log' => [
                    'id' => $log->getId(),
                    'equipment' => $log->getEquipment()->getName(),
                    'weightKg' => $log->getWeightKg(),
                    'reps' => $log->getReps(),
                    'sets' => $log->getSets(),
                    'estimated1Rm' => $log->getEstimated1Rm(),
                    'isPR' => $log->isPersonalRecord(),
                    'seatSetting' => $log->getSeatSetting(),
                    'loggedAt' => $log->getLoggedAt()->format(\DateTimeInterface::ATOM),
                ]
            ], Response::HTTP_CREATED);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/pr-vault', name: 'pr_vault', methods: ['GET'])]
    public function prVault(#[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $vault = $this->equipmentService->getMemberPrVault($member);
        return $this->json([
            'memberId' => $member->getId(),
            'vault' => $vault,
        ]);
    }

    #[Route('/catalog', name: 'catalog', methods: ['GET'])]
    public function catalog(): JsonResponse
    {
        $this->equipmentService->seedDefaultEquipment();
        $all = $this->equipmentRepository->findAll();

        $data = array_map(function ($e) {
            return [
                'id' => $e->getId(),
                'qrCode' => $e->getQrCode(),
                'name' => $e->getName(),
                'category' => $e->getCategory(),
                'targetMuscles' => $e->getTargetMuscles(),
                'modelNumber' => $e->getModelNumber(),
                'imageUrl' => $e->getImageUrl(),
            ];
        }, $all);

        return $this->json($data);
    }
}

