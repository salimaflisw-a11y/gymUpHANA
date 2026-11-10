<?php

namespace App\Shared\Controller;

use App\Shared\Service\FirebaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/firebase', name: 'api_firebase_')]
class FirebaseStatusController extends AbstractController
{
    public function __construct(
        private readonly FirebaseService $firebaseService
    ) {
    }

    #[Route('/status', name: 'status', methods: ['GET'])]
    public function getStatus(): JsonResponse
    {
        return $this->json([
            'success'   => true,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'firebase'  => $this->firebaseService->getStatus(),
        ]);
    }
}

