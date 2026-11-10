<?php

namespace App\Shared\Service;

use Google\Cloud\Firestore\FirestoreClient;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FirebaseService
{
    private ?FirestoreClient $firestore = null;
    private bool $isLive = false;
    private string $resolvedProjectId = '';
    private string $localStorageFile;
    private array $localStore = [];
    private LoggerInterface $logger;

    private string $databaseName;

    public function __construct(
        private readonly ?string $credentialsPath = null,
        private readonly ?string $projectId = null,
        ?string $database = '(default)',
        private readonly ?string $emulatorHost = null,
        private readonly ?string $caBundle = null,
        private readonly bool $autoSync = true,
        private readonly string $projectDir = '',
        ?LoggerInterface $logger = null
    ) {
        $this->databaseName = $database ?: '(default)';
        $this->logger = $logger ?? new NullLogger();
        $this->localStorageFile = ($this->projectDir ?: sys_get_temp_dir()) . '/var/firebase_local_store.json';
        $this->initialize();
    }

    private function initialize(): void
    {
        $resolvedPath = $this->resolvePath($this->credentialsPath);

        // 1. Check if emulator is configured
        if ($this->emulatorHost) {
            putenv('FIRESTORE_EMULATOR_HOST=' . $this->emulatorHost);
            $_ENV['FIRESTORE_EMULATOR_HOST'] = $this->emulatorHost;
        }

        // 2. Attempt live Firebase initialization if credentials file exists
        if ($resolvedPath && file_exists($resolvedPath) && is_file($resolvedPath)) {
            $jsonContent = @file_get_contents($resolvedPath);
            $parsed = json_decode($jsonContent, true);

            if (is_array($parsed) && !empty($parsed['project_id']) && !str_starts_with($parsed['project_id'], 'your-firebase')) {
                try {
                    $this->resolvedProjectId = $this->projectId ?: ($parsed['project_id'] ?? '');

                    // Point cURL at a trusted CA bundle while preserving TLS
                    // certificate validation for all Firestore traffic.
                    $guzzleOptions = ['timeout' => 20.0];
                    if ($this->caBundle && is_file($this->caBundle)) {
                        $guzzleOptions['verify'] = $this->caBundle;
                    }
                    $httpHandler = HttpHandlerFactory::build(new Client($guzzleOptions));
                    $this->firestore = new FirestoreClient([
                        'credentials' => $parsed,
                        'projectId'   => $this->resolvedProjectId,
                        'database'    => $this->databaseName,
                        'transport'   => 'rest',
                        'transportConfig' => [
                            'rest' => ['httpHandler' => [$httpHandler, 'async']],
                        ],
                    ]);

                    $this->isLive = true;
                    $this->logger->info('Firebase Firestore initialized successfully for project: ' . $this->resolvedProjectId);
                    return;
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to initialize live Firebase: ' . $e->getMessage() . '. Falling back to local store.');
                }
            }
        }

        // 3. Resilient fallback mode (local JSON store)
        $this->loadLocalStore();
        $this->resolvedProjectId = $this->projectId ?: 'gymup-local-fallback';
        $this->isLive = false;
        $this->logger->info('FirebaseService running in local mock fallback mode (' . $this->localStorageFile . ')');
    }

    private function resolvePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, '%kernel.project_dir%')) {
            $path = str_replace('%kernel.project_dir%', $this->projectDir, $path);
        }

        return $path;
    }

    public function isLive(): bool
    {
        return $this->isLive;
    }

    public function isAutoSyncEnabled(): bool
    {
        return $this->autoSync;
    }

    public function getProjectId(): string
    {
        return $this->resolvedProjectId;
    }

    public function getFirestore(): ?FirestoreClient
    {
        return $this->firestore;
    }

    public function getStatus(): array
    {
        $collections = ['users', 'membership_plans', 'subscriptions', 'class_sessions', 'bookings', 'check_ins', 'streaks', 'payments', 'job_postings', 'applications'];
        $counts = [];

        if ($this->isLive && $this->firestore) {
            foreach ($collections as $col) {
                // Do not perform a network read for every collection in a
                // health/status response. It makes the sync command slow and
                // can prevent the actual upload from ever starting.
                $counts[$col] = 'configured';
            }
        } else {
            foreach ($collections as $col) {
                $counts[$col] = isset($this->localStore[$col]) ? count($this->localStore[$col]) . ' local' : 'empty';
            }
        }

        return [
            'mode'            => $this->isLive ? 'live_firestore' : 'local_json_fallback',
            'is_live'         => $this->isLive,
            'project_id'      => $this->resolvedProjectId,
            'database'        => $this->databaseName,
            'credentials_set' => ($this->credentialsPath && file_exists($this->resolvePath($this->credentialsPath))),
            'auto_sync'       => $this->autoSync,
            'collections'     => $counts,
        ];
    }

    // ==========================================
    // Unified Firestore CRUD Operations
    // ==========================================

    /**
     * Set/Update document data.
     */
    public function setDocument(string $collection, string $id, array $data, bool $merge = true): bool
    {
        $cleanData = $this->normalizeData($data);
        $cleanData['_updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        if ($this->isLive && $this->firestore) {
            try {
                $docRef = $this->firestore->collection($collection)->document($id);
                $docRef->set($cleanData, ['merge' => $merge]);
                return true;
            } catch (\Throwable $e) {
                $this->logger->error("Firestore setDocument error [{$collection}/{$id}]: " . $e->getMessage());
                // Fallback store
                $this->saveLocalDocument($collection, $id, $cleanData, $merge);
                return false;
            }
        }

        return $this->saveLocalDocument($collection, $id, $cleanData, $merge);
    }

    /**
     * Add document with auto-generated ID.
     */
    public function addDocument(string $collection, array $data): string
    {
        $cleanData = $this->normalizeData($data);
        $cleanData['_created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        if ($this->isLive && $this->firestore) {
            try {
                $ref = $this->firestore->collection($collection)->add($cleanData);
                return $ref->id();
            } catch (\Throwable $e) {
                $this->logger->error("Firestore addDocument error [{$collection}]: " . $e->getMessage());
            }
        }

        $id = bin2hex(random_bytes(10));
        $this->saveLocalDocument($collection, $id, $cleanData, false);
        return $id;
    }

    /**
     * Get document by collection and ID.
     */
    public function getDocument(string $collection, string $id): ?array
    {
        if ($this->isLive && $this->firestore) {
            try {
                $snapshot = $this->firestore->collection($collection)->document($id)->snapshot();
                if ($snapshot->exists()) {
                    $data = $snapshot->data();
                    $data['id'] = $id;
                    return $data;
                }
                return null;
            } catch (\Throwable $e) {
                $this->logger->error("Firestore getDocument error [{$collection}/{$id}]: " . $e->getMessage());
            }
        }

        $this->loadLocalStore();
        return $this->localStore[$collection][$id] ?? null;
    }

    /**
     * Delete document by collection and ID.
     */
    public function deleteDocument(string $collection, string $id): bool
    {
        if ($this->isLive && $this->firestore) {
            try {
                $this->firestore->collection($collection)->document($id)->delete();
            } catch (\Throwable $e) {
                $this->logger->error("Firestore deleteDocument error [{$collection}/{$id}]: " . $e->getMessage());
            }
        }

        $this->loadLocalStore();
        if (isset($this->localStore[$collection][$id])) {
            unset($this->localStore[$collection][$id]);
            $this->persistLocalStore();
        }

        return true;
    }

    /**
     * Query documents from a collection.
     */
    public function listDocuments(
        string $collection,
        array $criteria = [],
        ?string $orderBy = null,
        string $direction = 'ASC',
        int $limit = 100
    ): array {
        if ($this->isLive && $this->firestore) {
            try {
                $query = $this->firestore->collection($collection);

                foreach ($criteria as $field => $value) {
                    $query = $query->where($field, '=', $value);
                }

                if ($orderBy) {
                    $query = $query->orderBy($orderBy, strtolower($direction) === 'desc' ? 'DESCENDING' : 'ASCENDING');
                }

                $query = $query->limit($limit);
                $snapshots = $query->documents();

                $results = [];
                foreach ($snapshots as $snapshot) {
                    if ($snapshot->exists()) {
                        $docData = $snapshot->data();
                        $docData['id'] = $snapshot->id();
                        $results[] = $docData;
                    }
                }
                return $results;
            } catch (\Throwable $e) {
                $this->logger->error("Firestore listDocuments error [{$collection}]: " . $e->getMessage());
            }
        }

        // Fallback to local store query
        $this->loadLocalStore();
        $items = array_values($this->localStore[$collection] ?? []);

        if (!empty($criteria)) {
            $items = array_filter($items, function ($item) use ($criteria) {
                foreach ($criteria as $k => $v) {
                    if (!isset($item[$k]) || $item[$k] !== $v) {
                        return false;
                    }
                }
                return true;
            });
        }

        if ($orderBy) {
            usort($items, function ($a, $b) use ($orderBy, $direction) {
                $valA = $a[$orderBy] ?? null;
                $valB = $b[$orderBy] ?? null;
                $cmp = $valA <=> $valB;
                return strtolower($direction) === 'desc' ? -$cmp : $cmp;
            });
        }

        return array_slice($items, 0, $limit);
    }

    // ==========================================
    // Local JSON Store Helpers
    // ==========================================

    private function loadLocalStore(): void
    {
        if (empty($this->localStore) && file_exists($this->localStorageFile)) {
            $content = @file_get_contents($this->localStorageFile);
            $parsed = json_decode($content, true);
            if (is_array($parsed)) {
                $this->localStore = $parsed;
            }
        }
    }

    private function persistLocalStore(): void
    {
        $dir = dirname($this->localStorageFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @file_put_contents($this->localStorageFile, json_encode($this->localStore, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function saveLocalDocument(string $collection, string $id, array $data, bool $merge): bool
    {
        $this->loadLocalStore();
        if (!isset($this->localStore[$collection])) {
            $this->localStore[$collection] = [];
        }

        $existing = $this->localStore[$collection][$id] ?? [];
        $data['id'] = $id;

        $this->localStore[$collection][$id] = $merge ? array_merge($existing, $data) : $data;
        $this->persistLocalStore();
        return true;
    }

    /**
     * Recursively normalize entities, DateTime instances, or objects into Firestore-safe arrays.
     */
    public function normalizeData(mixed $data): mixed
    {
        if ($data instanceof \DateTimeInterface) {
            return $data->format(\DateTimeInterface::ATOM);
        }

        if (is_object($data)) {
            if (method_exists($data, 'getId')) {
                return (string) $data->getId();
            }
            if (method_exists($data, 'toArray')) {
                return $this->normalizeData($data->toArray());
            }
            return (string) $data;
        }

        if (is_array($data)) {
            $normalized = [];
            foreach ($data as $key => $value) {
                if (in_array($key, ['password', '__initializer__', '__cloner__', '__isInitialized__'], true)) {
                    continue;
                }
                $normalized[$key] = $this->normalizeData($value);
            }
            return $normalized;
        }

        return $data;
    }
}
