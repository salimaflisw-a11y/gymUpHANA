<?php

namespace App\Gamification\Repository;

use App\Auth\Entity\User;
use App\Gamification\Entity\Badge;
use App\Gamification\Entity\MemberBadge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MemberBadge>
 */
class MemberBadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MemberBadge::class);
    }

    /**
     * @return list<MemberBadge>
     */
    public function findByMember(User $member): array
    {
        return $this->createQueryBuilder('mb')
            ->join('mb.badge', 'b')
            ->addSelect('b')
            ->where('mb.member = :member')
            ->setParameter('member', $member)
            ->orderBy('mb.unlockedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasBadge(User $member, Badge $badge): bool
    {
        $count = $this->createQueryBuilder('mb')
            ->select('COUNT(mb.id)')
            ->where('mb.member = :member')
            ->andWhere('mb.badge = :badge')
            ->setParameter('member', $member)
            ->setParameter('badge', $badge)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}

