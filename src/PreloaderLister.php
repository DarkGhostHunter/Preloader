<?php

namespace Ninja\Preloader;

use const DIRECTORY_SEPARATOR;

class PreloaderLister
{
    public array $list;
    public float $memory = Preloader::MEMORY_LIMIT;
    public bool $selfExclude = false;
    public array $appended = [];
    public array $excluded = [];

    public function build(): array
    {
        // Exclude "$PRELOAD$" phantom file
        $scripts = $this->excludePreloadVariable($this->list);

        // Exclude the files set by the developer
        $scripts = $this->exclude($scripts);

        // Sort the scripts by hit ratio
        $scripts = $this->sortScripts($scripts);

        // Cull the list by memory usage
        $scripts = $this->cutByMemoryLimit($scripts);

        // Add files to the preload.
        $scripts = array_merge($scripts, $this->appended);

        // Remove duplicates and return
        return array_unique($scripts);
    }

    protected function excludePreloadVariable(array $list): array
    {
        unset($list['$PRELOAD$']);

        return $list;
    }

    protected function sortScripts(array $scripts): array
    {
        // There is no problem here with the Preloader.
        array_multisort(
            array_column($scripts, 'hits'),
            SORT_DESC,
            array_column($scripts, 'last_used_timestamp'),
            SORT_DESC,
            $scripts
        );

        return $scripts;
    }

    protected function cutByMemoryLimit($files): array
    {
        // Exit early if the memory limit is zero (disabled).
        if (! $limit = $this->memory * 1024 ** 2) {
            return array_keys($files);
        }

        $cumulative = 0;

        $resulting = [];

        // We will cycle through each file and check how much memory it consumes. If adding
        // the file exceeds the memory limit set, we will stop adding files to the compiled
        // list of preloaded files. Otherwise, we'll keep cycling and return all the files.
        foreach ($files as $key => $file) {
            $cumulative += $file['memory_consumption'];

            if ($cumulative > $limit) {
                return $resulting;
            }

            $resulting[] = $key;
        }

        return $resulting;
    }

    protected function exclude(array $scripts): array
    {
        return array_diff_key($scripts, array_flip(array_merge($this->excluded, $this->excludedPackageFiles())));
    }

    protected function excludedPackageFiles(): array
    {
        if ($this->selfExclude) {
            return array_map(
                static fn ($entry) => realpath($entry),
                glob(realpath(__DIR__) . DIRECTORY_SEPARATOR . '*.php')
            );
        }

        return [];
    }
}
