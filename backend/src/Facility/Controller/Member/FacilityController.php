<?php

namespace App\Facility\Controller\Member;

use App\Auth\Entity\User;
use App\Facility\Service\FacilityService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/facility', name: 'api_facility_')]
class FacilityController extends AbstractController
{
    public function __construct(
        private readonly FacilityService $facilityService
    ) {
    }

    #[Route('/occupancy', name: 'live_occupancy', methods: ['GET'])]
    public function liveOccupancy(): JsonResponse
    {
        return $this->json($this->facilityService->getCurrentOccupancy());
    }

    #[Route('/forecast', name: 'crowd_forecast', methods: ['GET'])]
    public function crowdForecast(): JsonResponse
    {
        return $this->json($this->facilityService->getWeeklyTrafficForecast());
    }

    #[Route('/access-pass', name: 'generate_pass', methods: ['GET'])]
    public function accessPass(#[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $pass = $this->facilityService->generateDynamicPass($member);
            return $this->json($pass);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }
}

