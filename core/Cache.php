<?php
/**
 * LightBlog CMS - Cache Manager
 * Simple file-based caching system
 */

class Cache {
    private $cache_dir;
    private $enabled;

    /**
     * Constructor
     */
    public function __construct() {
        $this->cache_dir = defined('CONTENT_PATH') ? CONTENT_PATH . '/cache/' : 'content/cache/';
        $this->enabled = defined('CACHE_ENABLED') ? CACHE_ENABLED : true;

        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }

    /**
     * Get cached content
     * @param string $key Cache key
     * @return mixed|null Cached content or null if not found/expired
     */
    public function get($key) {
        if (!$this->enabled) {
            return null;
        }

        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));

        // Check if expired
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $data['content'];
    }

    /**
     * Set cache content
     * @param string $key Cache key
     * @param mixed $content Content to cache
     * @param int $ttl Time to live in seconds (0 = never expires)
     * @return bool Success
     */
    public function set($key, $content, $ttl = 3600) {
        if (!$this->enabled) {
            return false;
        }

        $file = $this->getCacheFile($key);

        $data = [
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'created' => time(),
            'content' => $content
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * Delete cached content
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return false;
    }

    /**
     * Clear all cache
     * @return int Number of files deleted
     */
    public function clear() {
        $files = glob($this->cache_dir . '*.cache');
        $count = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Clear expired cache
     * @return int Number of files deleted
     */
    public function clearExpired() {
        $files = glob($this->cache_dir . '*.cache');
        $count = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $data = unserialize(file_get_contents($file));
                if ($data['expires'] > 0 && $data['expires'] < time()) {
                    unlink($file);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check if key exists in cache
     * @param string $key Cache key
     * @return bool
     */
    public function has($key) {
        return $this->get($key) !== null;
    }

    /**
     * Get cache file path for a key
     * @param string $key Cache key
     * @return string File path
     */
    private function getCacheFile($key) {
        return $this->cache_dir . md5($key) . '.cache';
    }

    /**
     * Get cache statistics
     * @return array Cache stats
     */
    public function getStats() {
        $files = glob($this->cache_dir . '*.cache');
        $total_size = 0;
        $expired = 0;
        $valid = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $total_size += filesize($file);
                $data = unserialize(file_get_contents($file));

                if ($data['expires'] > 0 && $data['expires'] < time()) {
                    $expired++;
                } else {
                    $valid++;
                }
            }
        }

        return [
            'total_files' => count($files),
            'valid_files' => $valid,
            'expired_files' => $expired,
            'total_size' => $total_size,
            'total_size_mb' => round($total_size / 1024 / 1024, 2)
        ];
    }

    /**
     * Remember - Get from cache or execute callback and cache result
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param int $ttl Time to live
     * @return mixed
     */
    public function remember($key, $callback, $ttl = 3600) {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = call_user_func($callback);
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Enable/disable caching
     * @param bool $enabled
     */
    public function setEnabled($enabled) {
        $this->enabled = $enabled;
    }

    /**
     * Check if caching is enabled
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }
}
