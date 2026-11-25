<?php

namespace App\Membership\Repository;

use App\Auth\Entity\User;
use App\Membership\Entity\Subscription;
use App\Shared\Enum\SubscriptionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function save(Subscription $subscription, bool $flush = true): void
    {
        $this->getEntityManager()->persist($subscription);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveSubscription(User $member): ?Subscription
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('s')
            ->andWhere('s.member = :member')
            ->andWhere('s.status = :status')
            ->andWhere('s.startDate <= :today')
            ->andWhere('s.endDate >= :today')
            ->setParameter('member', $member)
            ->setParameter('status', SubscriptionStatus::ACTIVE)
            ->setParameter('today', $today)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Subscription[]
     */
    public function findByMember(User $member): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.member = :member')
            ->setParameter('member', $member)
            ->orderBy('s.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Number of distinct members holding a currently valid active subscription.
     */
    public function countActiveMembers(): int
    {
        $today = new \DateTimeImmutable('today');

        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT s.member)')
            ->andWhere('s.status = :status')
            ->andWhere('s.startDate <= :today')
            ->andWhere('s.endDate >= :today')
            ->setParameter('status', SubscriptionStatus::ACTIVE)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Number of distinct members whose subscription covered a given date.
     */
    public function countMembersActiveOn(\DateTimeInterface $date): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT s.member)')
            ->andWhere('s.status != :cancelled')
            ->andWhere('s.startDate <= :date')
            ->andWhere('s.endDate >= :date')
            ->setParameter('cancelled', SubscriptionStatus::CANCELLED)
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
