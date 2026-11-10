<?php

namespace App\Shared\Command;

use App\Attendance\Entity\CheckIn;
use App\Attendance\Entity\Schedule;
use App\Attendance\Entity\Streak;
use App\Auth\Entity\User;
use App\Booking\Entity\Booking;
use App\Booking\Entity\ClassSession;
use App\Equipment\Entity\Equipment;
use App\Equipment\Entity\EquipmentLog;
use App\Gamification\Entity\Badge;
use App\Gamification\Entity\CommunityChallenge;
use App\Gamification\Entity\MemberBadge;
use App\Hiring\Entity\Application;
use App\Hiring\Entity\JobPosting;
use App\Membership\Entity\Plan;
use App\Membership\Entity\Subscription;
use App\Payment\Entity\Payment;
use App\Social\Entity\BuddyMatch;
use App\Social\Entity\BuddyProfile;
use App\Social\Entity\FormReview;
use App\Shared\EventListener\FirebaseEntitySyncListener;
use App\Shared\Service\FirebaseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'firebase:sync',
    description: 'Test Firebase connection and sync all database records to Cloud Firestore.'
)]
class FirebaseSyncCommand extends Command
{
    private const SYNC_CLASSES = [
        'users'             => User::class,
        'membership_plans'  => Plan::class,
        'subscriptions'     => Subscription::class,
        'class_sessions'    => ClassSession::class,
        'bookings'          => Booking::class,
        'waitlist_entries'  => \App\Booking\Entity\WaitlistEntry::class,
        'check_ins'         => CheckIn::class,
        'streaks'           => Streak::class,
        'schedules'         => Schedule::class,
        'payments'          => Payment::class,
        'job_postings'      => JobPosting::class,
        'applications'      => Application::class,
        'equipment'         => Equipment::class,
        'equipment_logs'    => EquipmentLog::class,
        'challenges'        => CommunityChallenge::class,
        'badges'            => Badge::class,
        'member_badges'     => MemberBadge::class,
        'buddy_profiles'    => BuddyProfile::class,
        'buddy_matches'     => BuddyMatch::class,
        'form_reviews'      => FormReview::class,
    ];

    public function __construct(
        private readonly FirebaseService $firebaseService,
        private readonly FirebaseEntitySyncListener $syncListener,
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('test', 't', InputOption::VALUE_NONE, 'Only test the Firebase Firestore connection without syncing')
            ->addOption('collection', 'c', InputOption::VALUE_OPTIONAL, 'Sync a specific collection (e.g. users, membership_plans)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('GymUp Firebase Firestore Integration');

        $status = $this->firebaseService->getStatus();

        $io->section('Status & Configuration');
        $io->table(
            ['Parameter', 'Value'],
            [
                ['Mode', $status['mode'] === 'live_firestore' ? '<fg=green>LIVE CLOUD FIRESTORE</>' : '<fg=yellow>LOCAL MOCK FALLBACK</>'],
                ['Project ID', $status['project_id']],
                ['Database', $status['database']],
                ['Credentials File Exists', $status['credentials_set'] ? '<fg=green>YES</>' : '<fg=red>NO (config/firebase/firebase_credentials.json missing)</>'],
                ['Auto-Sync Enabled', $status['auto_sync'] ? '<fg=green>YES</>' : '<fg=yellow>NO</>'],
            ]
        );

        if (!$status['credentials_set']) {
            $io->warning([
                'No service account credentials file found at: config/firebase/firebase_credentials.json',
                'GymUp is operating in local storage mode.',
                'To enable live Firestore synchronization:',
                '  1. Open Firebase Console -> Project Settings -> Service Accounts',
                '  2. Click "Generate new private key"',
                '  3. Save the JSON file as config/firebase/firebase_credentials.json',
            ]);
        }

        if ($input->getOption('test')) {
            if ($status['is_live']) {
                $io->success('Firebase Firestore connection test successful!');
            } else {
                $io->note('Operating in local fallback mode. No active connection to Cloud Firestore.');
            }
            return Command::SUCCESS;
        }

        $targetCollection = $input->getOption('collection');
        $classesToSync = self::SYNC_CLASSES;

        if ($targetCollection) {
            if (!isset(self::SYNC_CLASSES[$targetCollection])) {
                $io->error("Unknown collection '{$targetCollection}'. Available: " . implode(', ', array_keys(self::SYNC_CLASSES)));
                return Command::FAILURE;
            }
            $classesToSync = [$targetCollection => self::SYNC_CLASSES[$targetCollection]];
        }

        $io->section('Syncing Database Entities to Firestore...');
        $totalSynced = 0;
        $summary = [];

        foreach ($classesToSync as $collectionName => $entityClass) {
            try {
                $repo = $this->em->getRepository($entityClass);
                $entities = $repo->findAll();
                $count = count($entities);

                foreach ($entities as $entity) {
                    if (!$this->syncListener->syncEntity($entity, true)) {
                        throw new \RuntimeException('Cloud Firestore sync failed. Check the application log for the network error.');
                    }
                }

                $totalSynced += $count;
                $summary[] = [$collectionName, $entityClass, $count, '<fg=green>SYNCED</>'];
            } catch (\Throwable $e) {
                $summary[] = [$collectionName, $entityClass, 0, '<fg=red>ERROR: ' . $e->getMessage() . '</>'];
            }
        }

        $io->table(['Collection', 'Entity Class', 'Records', 'Status'], $summary);
        $io->success("Successfully synchronized {$totalSynced} records to Firestore collections.");

        return Command::SUCCESS;
    }
}

