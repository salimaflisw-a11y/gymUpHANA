<?php

namespace App\Equipment\Repository;

use App\Equipment\Entity\Equipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipment>
 */
class EquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipment::class);
    }

    public function findByQrCode(string $qrCode): ?Equipment
    {
        return $this->findOneBy(['qrCode' => $qrCode]);
    }
}

