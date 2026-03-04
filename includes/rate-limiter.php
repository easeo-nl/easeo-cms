<?php
/**
 * EASEO CMS — IP-based rate limiting
 */

class RateLimiter {
    private string $storageDir;
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(int $maxAttempts = 5, int $windowSeconds = 900, string $context = 'default') {
        $this->storageDir = EASEO_DATA . '/rate_limits';
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }

        $this->cleanup();
    }

    public function isLimited(string $ip = null): bool {
        $ip = $ip ?? $this->getIp();
        $data = $this->getData($ip);

        // Remove expired attempts
        $cutoff = time() - $this->windowSeconds;
        $data = array_filter($data, fn($t) => $t > $cutoff);

        return count($data) >= $this->maxAttempts;
    }

    public function hit(string $ip = null): void {
        $ip = $ip ?? $this->getIp();
        $data = $this->getData($ip);

        $cutoff = time() - $this->windowSeconds;
        $data = array_filter($data, fn($t) => $t > $cutoff);
        $data[] = time();

        $this->saveData($ip, array_values($data));
    }

    public function reset(string $ip = null): void {
        $ip = $ip ?? $this->getIp();
        $file = $this->getFile($ip);
        if (file_exists($file)) unlink($file);
    }

    private function getIp(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function getFile(string $ip): string {
        return $this->storageDir . '/' . md5($ip) . '.json';
    }

    private function getData(string $ip): array {
        $file = $this->getFile($ip);
        if (!file_exists($file)) return [];
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    private function saveData(string $ip, array $data): void {
        file_put_contents($this->getFile($ip), json_encode($data));
    }

    private function cleanup(): void {
        // Clean old files occasionally (1% chance per request)
        if (mt_rand(1, 100) !== 1) return;

        $cutoff = time() - $this->windowSeconds * 2;
        foreach (glob($this->storageDir . '/*.json') as $file) {
            if (filemtime($file) < $cutoff) unlink($file);
        }
    }
}
