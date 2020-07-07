<?php

namespace DarkGhostHunter\Preloader;

trait GeneratesScript
{
    /**
     * Memory limit (in MB).
     *
     * @var float
     */
    protected $memory = self::MEMORY_LIMIT;

    /**
     * If it should use `require_once` to preload files.
     *
     * @var bool
     */
    protected bool $useRequire = false;

    /**
     * Location of the Autoloader.
     *
     * @var string
     */
    protected ?string $autoloader = null;

    /**
     * If the script should ignore files not found.
     *
     * @var bool
     */
    protected bool $ignoreNotFound = false;

    /**
     * Memory limit (in MB) to constrain the file list.
     *
     * @param  int|float  $limit
     * @return $this
     *
     * @throws \RuntimeException
     */
    public function memoryLimit($limit) : self
    {
        $this->memory = (float)$limit;

        return $this;
    }

    /**
     * Use `require_once $file` to preload each file on the list.
     *
     * @param  string  $autoloader
     * @return $this
     */
    public function useRequire(string $autoloader) : self
    {
        $this->useRequire = true;
        $this->autoloader = $autoloader;

        return $this;
    }

    /**
     * If it should NOT throw an exception when a file to preload doesn't exists.
     *
     * @param  bool  $ignore
     * @return $this
     */
    public function ignoreNotFound($ignore = true)
    {
        $this->ignoreNotFound = $ignore;

        return $this;
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
            '@opcache_memory_used'   =>
                number_format($opcache['memory_usage']['used_memory'] / 1024 ** 2, 1, '.', ''),
            '@opcache_memory_free'   =>
                number_format($opcache['memory_usage']['free_memory'] / 1024 ** 2, 1, '.', ''),
            '@opcache_memory_wasted' =>
                number_format($opcache['memory_usage']['wasted_memory'] / 1024 ** 2, 1, '.', ''),
            '@opcache_files'         =>
                $opcache['opcache_statistics']['num_cached_scripts'],
            '@opcache_hit_rate'      =>
                number_format($opcache['opcache_statistics']['opcache_hit_rate'], 2, '.', ''),
            '@opcache_misses'        =>
                $opcache['opcache_statistics']['misses'],
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
            '@preloader_memory_limit' => $this->memory ? $this->memory . ' MB' : '(disabled)',
            '@preloader_overwrite'    => $this->overwrite ? 'true' : 'false',
            '@preloader_appended'     => count($this->lister->appended),
            '@preloader_excluded'     => count($this->lister->excluded),
        ];
    }
}
