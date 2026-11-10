<?php

namespace App\Facility\Controller\Receptionist;

use App\Facility\Service\FacilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/facility/turnstile', name: 'api_facility_turnstile_')]
class AccessPassController extends AbstractController
{
    public function __construct(
        private readonly FacilityService $facilityService
    ) {
    }

    #[Route('/verify', name: 'verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return $this->json(['valid' => false, 'error' => 'Pass token is required.'], Response::HTTP_BAD_REQUEST);
        }

        // Strip GYMUP://PASS/ prefix if scanner passed full URI
        if (str_starts_with($token, 'GYMUP://PASS/')) {
            $token = substr($token, strlen('GYMUP://PASS/'));
        }

        $result = $this->facilityService->verifyDynamicPass($token);

        if (!$result['valid']) {
            return $this->json($result, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($result, Response::HTTP_OK);
    }
}

