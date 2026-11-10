<?php

namespace App\Booking\Repository;

use App\Auth\Entity\User;
use App\Booking\Entity\ClassSession;
use App\Booking\Entity\WaitlistEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WaitlistEntry>
 */
class WaitlistEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WaitlistEntry::class);
    }

    public function findActiveBySessionAndMember(ClassSession $session, User $member): ?WaitlistEntry
    {
        return $this->createQueryBuilder('w')
            ->where('w.classSession = :session')
            ->andWhere('w.member = :member')
            ->andWhere('w.status IN (:activeStatuses)')
            ->setParameter('session', $session)
            ->setParameter('member', $member)
            ->setParameter('activeStatuses', ['WAITING', 'OFFERED'])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextInQueue(ClassSession $session): ?WaitlistEntry
    {
        return $this->createQueryBuilder('w')
            ->where('w.classSession = :session')
            ->andWhere('w.status = :status')
            ->setParameter('session', $session)
            ->setParameter('status', 'WAITING')
            ->orderBy('w.queuePosition', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<WaitlistEntry>
     */
    public function findByMember(User $member): array
    {
        return $this->createQueryBuilder('w')
            ->join('w.classSession', 'cs')
            ->addSelect('cs')
            ->where('w.member = :member')
            ->andWhere('w.status IN (:statuses)')
            ->setParameter('member', $member)
            ->setParameter('statuses', ['WAITING', 'OFFERED'])
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countQueue(ClassSession $session): int
    {
        $count = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.classSession = :session')
            ->andWhere('w.status = :status')
            ->setParameter('session', $session)
            ->setParameter('status', 'WAITING')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }
}

