<?php

namespace App\Booking\Service;

use App\Auth\Entity\User;
use App\Booking\Entity\Booking;
use App\Booking\Entity\ClassSession;
use App\Booking\Entity\WaitlistEntry;
use App\Booking\Repository\BookingRepository;
use App\Booking\Repository\ClassSessionRepository;
use App\Booking\Repository\WaitlistEntryRepository;
use App\Membership\Repository\SubscriptionRepository;
use App\Shared\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;

class WaitlistService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookingRepository $bookingRepository,
        private readonly ClassSessionRepository $classSessionRepository,
        private readonly WaitlistEntryRepository $waitlistRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly ConflictCheckerService $conflictCheckerService
    ) {
    }

    /**
     * Builds interactive visual floor plan layout for a class session.
     */
    public function getStudioLayout(ClassSession $session, ?User $currentMember = null): array
    {
        $capacity = $session->getCapacity();
        $isSpinning = str_contains(strtolower($session->getName()), 'spin') || str_contains(strtolower($session->getRoom()), 'spin') || str_contains(strtolower($session->getRoom()), 'bike');
        $isYoga = str_contains(strtolower($session->getName()), 'yoga') || str_contains(strtolower($session->getName()), 'pilates');

        $spotType = $isSpinning ? 'BIKE' : ($isYoga ? 'MAT' : 'STATION');
        $prefix = $isSpinning ? 'Bike' : ($isYoga ? 'Mat' : 'Spot');

        $activeBookings = $this->bookingRepository->findBy([
            'classSession' => $session,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $occupiedSpots = [];
        $memberSpotNumber = null;

        foreach ($activeBookings as $b) {
            $spotNum = $b->getSpotNumber();
            if ($spotNum !== null) {
                $occupiedSpots[$spotNum] = true;
                if ($currentMember && $b->getMember()->getId() === $currentMember->getId()) {
                    $memberSpotNumber = $spotNum;
                }
            }
        }

        // Layout into 5 columns
        $cols = 5;
        $spots = [];
        for ($i = 1; $i <= $capacity; $i++) {
            $row = (int) ceil($i / $cols);
            $col = (($i - 1) % $cols) + 1;

            $isOccupied = isset($occupiedSpots[$i]);
            $isMine = ($memberSpotNumber === $i);

            $spots[] = [
                'spotNumber' => $i,
                'label' => sprintf('%s %02d', $prefix, $i),
                'row' => $row,
                'col' => $col,
                'type' => $spotType,
                'status' => $isMine ? 'YOUR_SPOT' : ($isOccupied ? 'BOOKED' : 'AVAILABLE'),
            ];
        }

        $waitlistCount = $this->waitlistRepository->countQueue($session);

        return [
            'classId' => $session->getId(),
            'className' => $session->getName(),
            'room' => $session->getRoom(),
            'capacity' => $capacity,
            'spotType' => $spotType,
            'availableSpots' => max(0, $capacity - count($activeBookings)),
            'mySpotNumber' => $memberSpotNumber,
            'waitlistCount' => $waitlistCount,
            'isFull' => count($activeBookings) >= $capacity,
            'layout' => [
                'columns' => $cols,
                'rows' => (int) ceil($capacity / $cols),
                'spots' => $spots,
            ],
        ];
    }

    /**
     * Reserve a specific chosen spot/bike in the studio layout.
     */
    public function reserveSpot(ClassSession $session, User $member, int $spotNumber): Booking
    {
        if ($spotNumber < 1 || $spotNumber > $session->getCapacity()) {
            throw new InvalidArgumentException(sprintf('Spot number %d is outside studio capacity.', $spotNumber));
        }

        // Check active subscription
        $subscription = $this->subscriptionRepository->findActiveSubscription($member);
        if (!$subscription) {
            throw new LogicException('You must have an active membership subscription to book a class.');
        }

        // Check if member already has active booking for this session
        $existing = $this->bookingRepository->findActiveByMemberAndSession($member, $session);
        if ($existing) {
            throw new LogicException('You are already booked for this class.');
        }

        // Check time conflict with member's other classes
        if ($this->conflictCheckerService->hasConflict($member, $session)) {
            throw new LogicException('You have a schedule conflict with another class at this time.');
        }

        // Check if spot is already taken
        $spotTaken = $this->bookingRepository->findOneBy([
            'classSession' => $session,
            'spotNumber' => $spotNumber,
            'status' => BookingStatus::CONFIRMED,
        ]);

        if ($spotTaken) {
            throw new LogicException(sprintf('Spot #%d is already reserved by another member. Please choose another spot.', $spotNumber));
        }

        $isSpinning = str_contains(strtolower($session->getName()), 'spin');
        $label = sprintf('%s %02d', $isSpinning ? 'Bike' : 'Spot', $spotNumber);

        $booking = new Booking($member, $session, BookingStatus::CONFIRMED, $spotNumber, $label);
        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    /**
     * Join waitlist queue when class is completely full.
     */
    public function joinWaitlist(ClassSession $session, User $member): WaitlistEntry
    {
        $subscription = $this->subscriptionRepository->findActiveSubscription($member);
        if (!$subscription) {
            throw new LogicException('You must have an active membership subscription to join the class waitlist.');
        }

        $existingBooking = $this->bookingRepository->findActiveByMemberAndSession($member, $session);
        if ($existingBooking) {
            throw new LogicException('You already have a confirmed booking for this class.');
        }

        $activeWaitlist = $this->waitlistRepository->findActiveBySessionAndMember($session, $member);
        if ($activeWaitlist) {
            throw new LogicException('You are already on the waitlist for this class.');
        }

        $currentQueueSize = $this->waitlistRepository->countQueue($session);
        $queuePosition = $currentQueueSize + 1;

        $entry = new WaitlistEntry($session, $member, $queuePosition);
        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return $entry;
    }

    /**
     * Automatically triggers when a booking is cancelled: offers the opening to next waitlist member.
     */
    public function processWaitlistOnCancellation(ClassSession $session): ?WaitlistEntry
    {
        $next = $this->waitlistRepository->getNextInQueue($session);
        if (!$next) {
            return null;
        }

        $next->offerSpot(15); // 15-minute exclusive claim window
        $this->entityManager->flush();

        return $next;
    }

    /**
     * Claim offered waitlist spot.
     */
    public function claimOfferedSpot(User $member, int $waitlistEntryId): Booking
    {
        $entry = $this->waitlistRepository->find($waitlistEntryId);
        if (!$entry || $entry->getMember()->getId() !== $member->getId()) {
            throw new LogicException('Waitlist entry not found.');
        }

        if (!$entry->isOfferValid()) {
            $entry->setStatus('EXPIRED');
            $this->entityManager->flush();
            throw new LogicException('Your 15-minute waitlist spot claim window has expired.');
        }

        $session = $entry->getClassSession();

        // Assign first available spot number
        $activeBookings = $this->bookingRepository->findBy([
            'classSession' => $session,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $occupied = [];
        foreach ($activeBookings as $b) {
            if ($b->getSpotNumber() !== null) {
                $occupied[$b->getSpotNumber()] = true;
            }
        }

        $assignedSpot = null;
        for ($i = 1; $i <= $session->getCapacity(); $i++) {
            if (!isset($occupied[$i])) {
                $assignedSpot = $i;
                break;
            }
        }

        $entry->setStatus('CLAIMED');
        $booking = new Booking(
            member: $member,
            classSession: $session,
            status: BookingStatus::CONFIRMED,
            spotNumber: $assignedSpot,
            spotLabel: $assignedSpot ? sprintf('Spot %02d', $assignedSpot) : null
        );

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }
}

