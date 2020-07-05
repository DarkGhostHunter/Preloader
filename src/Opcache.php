<?php

namespace DarkGhostHunter\Preloader;

use RuntimeException;

/**
 * Class Opcache
 *
 * This is just a class to enable mocking for testing
 */
class Opcache
{
    /**
     * Here we will save the Opcache status instead of retrieving it every time.
     *
     * @var array|false
     */
    protected $status;

    /**
     * Get status information about the cache
     *
     * @see https://www.php.net/manual/en/function.opcache-get-status.php
     * @return array
     * @throws \RuntimeException When Opcache is disabled.
     */
    public function getStatus() : array
    {
        if ($this->status ??= opcache_get_status(true)) {
            return $this->status;
        }

        throw new RuntimeException(
            'Opcache is disabled. Further reference: https://www.php.net/manual/en/opcache.configuration'
        );
    }

    /**
     * Returns if Opcache is enabled
     *
     * @return bool
     */
    public function isEnabled() : bool
    {
        return $this->getStatus()['opcache_enabled'];
    }

    /**
     * Returns the scripts used by Opcache
     *
     * @return array
     */
    public function getScripts() : array
    {
        return $this->getStatus()['scripts'];
    }

    /**
     * Returns the number of scripts cached
     *
     * @return int
     */
    public function getNumberCachedScripts()
    {
        return $this->getStatus()['opcache_statistics']['num_cached_scripts'];
    }

    /**
     * Returns the number of hits in Opcache
     *
     * @return mixed
     */
    public function getHits()
    {
        return $this->getStatus()['opcache_statistics']['hits'];
    }
}
