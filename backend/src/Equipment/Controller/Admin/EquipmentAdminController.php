<?php

namespace App\Equipment\Controller\Admin;

use App\Equipment\Entity\Equipment;
use App\Equipment\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/equipment', name: 'api_admin_equipment_')]
#[IsGranted('ROLE_ADMIN')]
class EquipmentAdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EquipmentRepository $equipmentRepository
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $all = $this->equipmentRepository->findAll();
        $data = array_map(function ($e) {
            return [
                'id' => $e->getId(),
                'qrCode' => $e->getQrCode(),
                'name' => $e->getName(),
                'category' => $e->getCategory(),
                'targetMuscles' => $e->getTargetMuscles(),
                'modelNumber' => $e->getModelNumber(),
            ];
        }, $all);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $qrCode = $data['qrCode'] ?? null;
        $name = $data['name'] ?? null;
        $category = $data['category'] ?? 'CHEST';
        $targetMuscles = $data['targetMuscles'] ?? [];
        $instructions = $data['setupInstructions'] ?? null;
        $videoUrl = $data['videoTutorialUrl'] ?? null;
        $imageUrl = $data['imageUrl'] ?? null;
        $modelNumber = $data['modelNumber'] ?? null;

        if (!$qrCode || !$name) {
            return $this->json(['error' => 'qrCode and name are required.'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->equipmentRepository->findByQrCode($qrCode)) {
            return $this->json(['error' => 'An equipment item with this QR code already exists.'], Response::HTTP_CONFLICT);
        }

        $equipment = new Equipment(
            qrCode: $qrCode,
            name: $name,
            category: $category,
            targetMuscles: $targetMuscles,
            setupInstructions: $instructions,
            videoTutorialUrl: $videoUrl,
            imageUrl: $imageUrl,
            modelNumber: $modelNumber
        );

        $this->entityManager->persist($equipment);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Equipment registered successfully.',
            'id' => $equipment->getId(),
        ], Response::HTTP_CREATED);
    }
}

