<?php

namespace DarkGhostHunter\Preloader;

use const DIRECTORY_SEPARATOR;

class PreloaderLister
{
    /**
     * List of opcache file list.
     *
     * @var array
     */
    public array $list;

    /**
     * Memory limit for the list.
     *
     * @var float
     */
    public float $memory = Preloader::MEMORY_LIMIT;

    /**
     * Exclude the package files.
     *
     * @var bool
     */
    public bool $selfExclude = false;

    /**
     * List of appended files.
     *
     * @var array
     */
    public array $appended = [];

    /**
     * List of excluded files.
     *
     * @var array
     */
    public array $excluded = [];

    /**
     * Builds a list of scripts.
     *
     * @return array
     */
    public function build() : array
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

    /**
     * Excludes de `$PRELOAD$` file key from the list.
     *
     * @param  array  $list
     * @return array
     */
    protected function excludePreloadVariable(array $list)
    {
        unset($list['$PRELOAD$']);

        return $list;
    }

    /**
     * Retrieve a sorted scripts list used by Opcache.
     *
     * @param  array $scripts
     * @return array
     */
    protected function sortScripts(array $scripts)
    {
        // There is no problem here with the Preloader.
        array_multisort(
            array_column($scripts, 'hits'), SORT_DESC,
            array_column($scripts, 'last_used_timestamp'), SORT_DESC,
            $scripts
        );

        return $scripts;
    }

    /**
     * Cuts the files by their cumulative memory consumption.
     *
     * @param $files
     * @return array
     */
    protected function cutByMemoryLimit($files)
    {
        // Exit early if the memory limit is zero (disabled).
        if (! $limit = $this->memory * 1024**2) {
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

    /**
     * Exclude files from the list
     *
     * @param  array  $scripts
     * @return array
     */
    protected function exclude(array $scripts)
    {
        return array_diff_key($scripts, array_flip(array_merge($this->excluded, $this->excludedPackageFiles())));
    }

    /**
     * Get the list of files excluded for this package
     *
     * @return array
     */
    protected function excludedPackageFiles()
    {
        if ($this->selfExclude) {
            return array_map(fn ($entry) => realpath($entry), glob(realpath(__DIR__) . DIRECTORY_SEPARATOR . '*.php'));
        }

        return [];
    }
}
