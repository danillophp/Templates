<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class CacheService
{
    private PDO $db;
    private string $fallbackPath;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->fallbackPath = __DIR__ . '/../../storage/cache';
        if (!is_dir($this->fallbackPath)) {
            @mkdir($this->fallbackPath, 0775, true);
        }
    }

    public function get(string $key): mixed
    {
        $stmt = $this->db->prepare('SELECT cache_value, expires_at FROM cache_store WHERE cache_key = :k LIMIT 1');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();

        if ($row && strtotime((string) $row['expires_at']) > time()) {
            $this->db->prepare('UPDATE cache_store SET hits = hits + 1, last_hit_at = NOW() WHERE cache_key = :k')->execute([':k' => $key]);
            return json_decode((string) $row['cache_value'], true);
        }

        return $this->getFileFallback($key);
    }

    public function set(string $key, mixed $value, int $ttl, array $tags = []): void
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $checksum = hash('sha256', (string) $encoded);

        $this->db->beginTransaction();
        try {
            $this->db->prepare('REPLACE INTO cache_store (cache_key, cache_value, created_at, expires_at, tags, checksum, hits, last_hit_at, last_modified_at) VALUES (:k, :v, NOW(), DATE_ADD(NOW(), INTERVAL :ttl SECOND), :tags, :checksum, 0, NULL, NOW())')
                ->execute([
                    ':k' => $key,
                    ':v' => $encoded,
                    ':ttl' => $ttl,
                    ':tags' => implode(',', $tags),
                    ':checksum' => $checksum,
                ]);

            $this->db->prepare('DELETE FROM cache_tags WHERE cache_key = :k')->execute([':k' => $key]);
            $tagStmt = $this->db->prepare('INSERT INTO cache_tags (tag_name, cache_key) VALUES (:tag, :k)');
            foreach ($tags as $tag) {
                $tagStmt->execute([':tag' => $tag, ':k' => $key]);
            }
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        $this->setFileFallback($key, $value, $ttl);
    }

    public function remember(string $key, int $ttl, array $tags, callable $callback): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $lockName = 'cache_lock_' . md5($key);
        $lockStmt = $this->db->prepare('SELECT GET_LOCK(:l, 5) AS got_lock');
        $lockStmt->execute([':l' => $lockName]);
        $gotLock = (int) ($lockStmt->fetch()['got_lock'] ?? 0) === 1;

        try {
            if ($gotLock) {
                $cachedAgain = $this->get($key);
                if ($cachedAgain !== null) {
                    return $cachedAgain;
                }

                $value = $callback();
                $this->set($key, $value, $ttl, $tags);
                return $value;
            }

            return $callback();
        } finally {
            if ($gotLock) {
                $releaseStmt = $this->db->prepare('SELECT RELEASE_LOCK(:l)');
                $releaseStmt->execute([':l' => $lockName]);
            }
        }
    }

    public function invalidateKey(string $key): void
    {
        $this->db->prepare('DELETE FROM cache_tags WHERE cache_key = :k')->execute([':k' => $key]);
        $this->db->prepare('DELETE FROM cache_store WHERE cache_key = :k')->execute([':k' => $key]);
        @unlink($this->fallbackPath . '/' . md5($key) . '.json');
    }

    public function invalidateTag(string $tag): void
    {
        $stmt = $this->db->prepare('SELECT cache_key FROM cache_tags WHERE tag_name = :tag');
        $stmt->execute([':tag' => $tag]);
        foreach ($stmt->fetchAll() as $row) {
            $this->invalidateKey((string) $row['cache_key']);
        }
    }

    public function purgeExpired(): int
    {
        $stmt = $this->db->prepare('SELECT cache_key FROM cache_store WHERE expires_at <= NOW()');
        $stmt->execute();
        $keys = $stmt->fetchAll();
        foreach ($keys as $row) {
            @unlink($this->fallbackPath . '/' . md5((string) $row['cache_key']) . '.json');
        }

        $this->db->exec('DELETE ct FROM cache_tags ct LEFT JOIN cache_store cs ON cs.cache_key = ct.cache_key WHERE cs.cache_key IS NULL');
        return $this->db->exec('DELETE FROM cache_store WHERE expires_at <= NOW()');
    }

    public function metadata(string $key): ?array
    {
        $stmt = $this->db->prepare('SELECT checksum, last_modified_at FROM cache_store WHERE cache_key = :k LIMIT 1');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function setFileFallback(string $key, mixed $value, int $ttl): void
    {
        $payload = ['expires_at' => time() + $ttl, 'value' => $value];
        @file_put_contents($this->fallbackPath . '/' . md5($key) . '.json', json_encode($payload));
    }

    private function getFileFallback(string $key): mixed
    {
        $file = $this->fallbackPath . '/' . md5($key) . '.json';
        if (!is_file($file)) {
            return null;
        }
        $payload = json_decode((string) file_get_contents($file), true);
        if (!is_array($payload) || (($payload['expires_at'] ?? 0) < time())) {
            @unlink($file);
            return null;
        }
        return $payload['value'] ?? null;
    }
}
