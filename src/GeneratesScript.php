<?php

namespace DarkGhostHunter\Preloader;

trait GeneratesScript
{
    /**
     * Returns if we should overwrite the preload script file
     *
     * @return bool
     */
    protected function shouldWrite()
    {
        return $this->overwrite ?: ! file_exists($this->output);
    }

    /**
     * Returns a digestible Opcache configuration
     *
     * @return array
     */
    protected function getOpcacheConfig()
    {
        $opcache = $this->opcache->getStatus();

        return [
            '@opcache_memory_used' => $opcache['memory_usage']['used_memory'],
            '@opcache_memory_free' => $opcache['memory_usage']['free_memory'],
            '@opcache_memory_wasted' => $opcache['memory_usage']['wasted_memory'],
            '@opcache_files' => $opcache['opcache_statistics']['num_cached_scripts'],
            '@opcache_hit_rate' => $opcache['opcache_statistics']['opcache_hit_rate'] * 100,
            '@opcache_misses' => $opcache['opcache_statistics']['misses'],
        ];
    }

    /**
     * Returns a digestible Preloader configuration
     *
     * @return array
     */
    protected function getPreloaderConfig()
    {
        return [
            '@preloader_memory_limit' => $this->lister->memory,
            '@preloader_overwrite' => ! $this->shouldWrite() ? 'true' : 'false',
            '@preloader_appended' => count($this->lister->append),
            '@preloader_excluded' => count($this->lister->exclude),
        ];
    }

}
