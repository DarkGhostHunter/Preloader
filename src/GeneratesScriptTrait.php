<?php

namespace Ninja\Preloader;

trait GeneratesScriptTrait
{
    protected float $memory = self::MEMORY_LIMIT;
    protected bool $useRequire = false;
    protected ?string $autoloader = null;
    protected bool $ignoreNotFound = false;

    public function memoryLimit(float|int $limit): self
    {
        $this->memory = (float)$limit;

        return $this;
    }

    public function useRequire(string $autoloader): self
    {
        $this->useRequire = true;
        $this->autoloader = $autoloader;

        return $this;
    }

    public function ignoreNotFound(bool $ignore = true): self
    {
        $this->ignoreNotFound = $ignore;

        return $this;
    }

    protected function getOpcacheConfig(): array
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

    protected function getPreloaderConfig(): array
    {
        return [
            '@preloader_memory_limit' => $this->memory ? $this->memory . ' MB' : '(disabled)',
            '@preloader_overwrite'    => $this->overwrite ? 'true' : 'false',
            '@preloader_appended'     => count($this->lister->appended),
            '@preloader_excluded'     => count($this->lister->excluded),
        ];
    }
}
