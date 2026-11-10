<?php

namespace App\Equipment\Repository;

use App\Auth\Entity\User;
use App\Equipment\Entity\Equipment;
use App\Equipment\Entity\EquipmentLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EquipmentLog>
 */
class EquipmentLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipmentLog::class);
    }

    /**
     * @return list<EquipmentLog>
     */
    public function findByMemberAndEquipment(User $member, Equipment $equipment, int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.member = :member')
            ->andWhere('l.equipment = :equipment')
            ->setParameter('member', $member)
            ->setParameter('equipment', $equipment)
            ->orderBy('l.loggedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findHighest1Rm(User $member, Equipment $equipment): ?float
    {
        $max = $this->createQueryBuilder('l')
            ->select('MAX(l.estimated1Rm)')
            ->where('l.member = :member')
            ->andWhere('l.equipment = :equipment')
            ->setParameter('member', $member)
            ->setParameter('equipment', $equipment)
            ->getQuery()
            ->getSingleScalarResult();

        return $max !== null ? (float) $max : null;
    }

    /**
     * @return list<EquipmentLog>
     */
    public function findMemberPersonalRecords(User $member): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.equipment', 'e')
            ->addSelect('e')
            ->where('l.member = :member')
            ->andWhere('l.isPersonalRecord = true')
            ->setParameter('member', $member)
            ->orderBy('l.loggedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

