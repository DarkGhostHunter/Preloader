<?php

namespace DarkGhostHunter\Preloader;

/**
 * Class Opcache
 *
 * This is just a class to enable mocking for testing
 *
 * @package DarkGhostHunter\Preloader
 */
class Opcache
{
    /**
     * Her we will save the Opcache status instead of retrieving it every time from Opcache
     *
     * @var array
     */
    protected array $status;

    /**
     * Get status information about the cache
     *
     * @see https://www.php.net/manual/en/function.opcache-get-status.php
     * @return array
     */
    public function getStatus() : array
    {
        return $this->status ??= opcache_get_status(true);
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