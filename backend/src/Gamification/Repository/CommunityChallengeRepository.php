<?php

namespace App\Gamification\Repository;

use App\Gamification\Entity\CommunityChallenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommunityChallenge>
 */
class CommunityChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityChallenge::class);
    }

    /**
     * @return list<CommunityChallenge>
     */
    public function findActiveChallenges(): array
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->andWhere('c.startDate <= :now')
            ->andWhere('c.endDate >= :now')
            ->setParameter('status', 'ACTIVE')
            ->setParameter('now', $now)
            ->orderBy('c.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

