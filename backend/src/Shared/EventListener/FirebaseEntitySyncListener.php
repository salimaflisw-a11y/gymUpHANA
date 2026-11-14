<?php

namespace App\Shared\EventListener;

use App\Shared\Service\FirebaseService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
class FirebaseEntitySyncListener
{
    private const ENTITY_COLLECTION_MAP = [
        'App\Auth\Entity\User'                     => 'users',
        'App\Membership\Entity\Plan'               => 'membership_plans',
        'App\Membership\Entity\Subscription'       => 'subscriptions',
        'App\Booking\Entity\ClassSession'          => 'class_sessions',
        'App\Booking\Entity\Booking'               => 'bookings',
        'App\Booking\Entity\WaitlistEntry'         => 'waitlist_entries',
        'App\Attendance\Entity\CheckIn'            => 'check_ins',
        'App\Attendance\Entity\Streak'             => 'streaks',
        'App\Attendance\Entity\Schedule'           => 'schedules',
        'App\Payment\Entity\Payment'               => 'payments',
        'App\Hiring\Entity\JobPosting'             => 'job_postings',
        'App\Hiring\Entity\Application'            => 'applications',
        'App\Equipment\Entity\Equipment'           => 'equipment',
        'App\Equipment\Entity\EquipmentLog'        => 'equipment_logs',
        'App\Gamification\Entity\CommunityChallenge'=> 'challenges',
        'App\Gamification\Entity\Badge'            => 'badges',
        'App\Gamification\Entity\MemberBadge'      => 'member_badges',
        'App\Social\Entity\BuddyProfile'           => 'buddy_profiles',
        'App\Social\Entity\BuddyMatch'             => 'buddy_matches',
        'App\Social\Entity\FormReview'             => 'form_reviews',
    ];

    private LoggerInterface $logger;

    public function __construct(
        private readonly FirebaseService $firebaseService,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->syncEntity($args->getObject(), false);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->syncEntity($args->getObject(), true);
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        if (!$this->firebaseService->isAutoSyncEnabled()) {
            return;
        }

        $entity = $args->getObject();
        $class = get_class($entity);

        $collection = $this->resolveCollection($class);
        if (!$collection) {
            return;
        }

        $id = method_exists($entity, 'getId') ? (string) $entity->getId() : null;
        if (!$id) {
            return;
        }

        try {
            $this->firebaseService->deleteDocument($collection, $id);
            $this->logger->info("Firebase: deleted [{$collection}/{$id}]");
        } catch (\Throwable $e) {
            $this->logger->error("Firebase sync error on delete [{$collection}/{$id}]: " . $e->getMessage());
        }
    }

    public function syncEntity(object $entity, bool $isUpdate = false): bool
    {
        if (!$this->firebaseService->isAutoSyncEnabled()) {
            return true;
        }

        $class = get_class($entity);
        $collection = $this->resolveCollection($class);
        if (!$collection) {
            return true;
        }

        $id = method_exists($entity, 'getId') ? (string) $entity->getId() : null;
        if (!$id) {
            return true;
        }

        try {
            $data = $this->extractEntityData($entity);
            $data['id'] = $id;
            $data['_synced_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

            $synced = $this->firebaseService->setDocument($collection, $id, $data, true);
            if ($this->firebaseService->isLive() && !$synced) {
                throw new \RuntimeException("Firestore rejected document {$collection}/{$id}; data was retained in the local fallback store.");
            }
            $this->logger->info("Firebase: synced [{$collection}/{$id}]");
            return true;
        } catch (\Throwable $e) {
            // Keep non-blocking so DB transactions succeed even if Firebase encounters temporary network delays
            $this->logger->error("Firebase sync error [{$collection}/{$id}]: " . $e->getMessage());
            return false;
        }
    }

    public function resolveCollection(string $className): ?string
    {
        foreach (self::ENTITY_COLLECTION_MAP as $entityClass => $collection) {
            if ($className === $entityClass || is_subclass_of($className, $entityClass)) {
                return $collection;
            }
        }
        return null;
    }

    public function extractEntityData(object $entity): array
    {
        $data = [];
        $refClass = new \ReflectionClass($entity);

        foreach ($refClass->getProperties() as $property) {
            $name = $property->getName();

            // Skip passwords and internal Doctrine properties
            if (in_array($name, ['password', '__cloner__', '__initializer__', '__isInitialized__'], true)) {
                continue;
            }

            if (!$property->isInitialized($entity)) {
                continue;
            }

            $value = $property->getValue($entity);

            // Handle enums
            if ($value instanceof \BackedEnum) {
                $data[$name] = $value->value;
                continue;
            }

            // Handle DateTime
            if ($value instanceof \DateTimeInterface) {
                $data[$name] = $value->format(\DateTimeInterface::ATOM);
                continue;
            }

            // Handle Doctrine collections: store count or simple IDs to prevent circular loops
            if ($value instanceof \Doctrine\Common\Collections\Collection) {
                $data[$name . 'Count'] = $value->count();
                continue;
            }

            // Handle related entity: store related ID
            if (is_object($value)) {
                if (method_exists($value, 'getId')) {
                    $data[$name . 'Id'] = (string) $value->getId();
                }
                continue;
            }

            $data[$name] = $value;
        }

        return $data;
    }
}

