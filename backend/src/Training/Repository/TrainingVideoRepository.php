<?php

namespace App\Training\Repository;

use App\Training\Entity\TrainingVideo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainingVideo>
 */
class TrainingVideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingVideo::class);
    }

    /**
     * @return TrainingVideo[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.category', 'ASC')
            ->addOrderBy('v.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TrainingVideo[]
     */
    public function search(?string $search = null, ?string $category = null, ?string $difficulty = null): array
    {
        $qb = $this->createQueryBuilder('v');

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere(
                'LOWER(v.title) LIKE :search
                OR LOWER(v.targetMuscle) LIKE :search
                OR LOWER(v.instructorName) LIKE :search
                OR LOWER(v.category) LIKE :search'
            )->setParameter('search', '%' . mb_strtolower(trim($search)) . '%');
        }

        if ($category !== null && $category !== '') {
            $qb->andWhere('v.category = :category')->setParameter('category', $category);
        }

        if ($difficulty !== null && $difficulty !== '') {
            $qb->andWhere('v.difficulty = :difficulty')->setParameter('difficulty', $difficulty);
        }

        return $qb->orderBy('v.category', 'ASC')
            ->addOrderBy('v.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
