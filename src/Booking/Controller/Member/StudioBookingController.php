<?php

namespace App\Booking\Controller\Member;

use App\Auth\Entity\User;
use App\Booking\Repository\ClassSessionRepository;
use App\Booking\Repository\WaitlistEntryRepository;
use App\Booking\Service\WaitlistService;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/booking/studio', name: 'api_booking_studio_')]
class StudioBookingController extends AbstractController
{
    public function __construct(
        private readonly WaitlistService $waitlistService,
        private readonly ClassSessionRepository $classSessionRepository,
        private readonly WaitlistEntryRepository $waitlistRepository
    ) {
    }

    #[Route('/classes/{id}/layout', name: 'class_layout', methods: ['GET'])]
    public function layout(int $id, #[CurrentUser] ?User $member): JsonResponse
    {
        $session = $this->classSessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Class session not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->waitlistService->getStudioLayout($session, $member);
        return $this->json($data);
    }

    #[Route('/classes/{id}/reserve-spot', name: 'reserve_spot', methods: ['POST'])]
    public function reserveSpot(int $id, Request $request, #[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->classSessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Class session not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $spotNumber = (int) ($data['spotNumber'] ?? 0);

        if ($spotNumber <= 0) {
            return $this->json(['error' => 'A valid spotNumber is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $booking = $this->waitlistService->reserveSpot($session, $member, $spotNumber);
            return $this->json([
                'success' => true,
                'message' => sprintf('Successfully reserved %s for %s!', $booking->getSpotLabel(), $session->getName()),
                'booking' => [
                    'id' => $booking->getId(),
                    'classId' => $session->getId(),
                    'className' => $session->getName(),
                    'spotNumber' => $booking->getSpotNumber(),
                    'spotLabel' => $booking->getSpotLabel(),
                    'bookedAt' => $booking->getBookedAt()->format(\DateTimeInterface::ATOM),
                ]
            ], Response::HTTP_CREATED);
        } catch (LogicException|InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/classes/{id}/waitlist', name: 'join_waitlist', methods: ['POST'])]
    public function joinWaitlist(int $id, #[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->classSessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Class session not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $entry = $this->waitlistService->joinWaitlist($session, $member);
            return $this->json([
                'success' => true,
                'message' => 'You have joined the class waitlist. You will be automatically notified if a spot opens!',
                'queuePosition' => $entry->getQueuePosition(),
                'status' => $entry->getStatus(),
            ], Response::HTTP_CREATED);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/my-waitlist', name: 'my_waitlist', methods: ['GET'])]
    public function myWaitlist(#[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $entries = $this->waitlistRepository->findByMember($member);
        $data = array_map(function ($w) {
            $session = $w->getClassSession();
            return [
                'id' => $w->getId(),
                'classId' => $session->getId(),
                'className' => $session->getName(),
                'dayOfWeek' => $session->getDayOfWeek(),
                'startTime' => $session->getStartTime()->format('H:i'),
                'queuePosition' => $w->getQueuePosition(),
                'status' => $w->getStatus(),
                'isOffered' => $w->isOfferValid(),
                'expiresAt' => $w->getExpiresAt()?->format(\DateTimeInterface::ATOM),
            ];
        }, $entries);

        return $this->json($data);
    }

    #[Route('/waitlist/{id}/claim', name: 'claim_spot', methods: ['POST'])]
    public function claimSpot(int $id, #[CurrentUser] ?User $member): JsonResponse
    {
        if (!$member) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $booking = $this->waitlistService->claimOfferedSpot($member, $id);
            return $this->json([
                'success' => true,
                'message' => 'Spot successfully claimed from waitlist!',
                'bookingId' => $booking->getId(),
                'spotLabel' => $booking->getSpotLabel(),
            ]);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}

