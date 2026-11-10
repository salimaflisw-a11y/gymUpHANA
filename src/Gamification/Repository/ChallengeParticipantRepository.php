<?php

namespace App\Gamification\Repository;

use App\Auth\Entity\User;
use App\Gamification\Entity\ChallengeParticipant;
use App\Gamification\Entity\CommunityChallenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChallengeParticipant>
 */
class ChallengeParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChallengeParticipant::class);
    }

    public function findByChallengeAndMember(CommunityChallenge $challenge, User $member): ?ChallengeParticipant
    {
        return $this->findOneBy(['challenge' => $challenge, 'member' => $member]);
    }

    /**
     * @return list<ChallengeParticipant>
     */
    public function findTopContributors(CommunityChallenge $challenge, int $limit = 10): array
    {
        return $this->createQueryBuilder('cp')
            ->join('cp.member', 'm')
            ->addSelect('m')
            ->where('cp.challenge = :challenge')
            ->setParameter('challenge', $challenge)
            ->orderBy('cp.contributedAmount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

