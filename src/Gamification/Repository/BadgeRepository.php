<?php

namespace App\Gamification\Repository;

use App\Gamification\Entity\Badge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Badge>
 */
class BadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Badge::class);
    }

    public function findByCode(string $code): ?Badge
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * @return list<Badge>
     */
    public function findAvailableForEvaluation(): array
    {
        return $this->findAll();
    }
}

