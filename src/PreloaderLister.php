<?php

namespace DarkGhostHunter\Preloader;

class PreloaderLister
{
    /**
     * Memory Limit (in MB) to constrain the file list
     *
     * @var int
     */
    public int $memory = 32;

    /**
     * List of files to append to the preload list
     *
     * @var array
     */
    public array $append = [];

    /**
     * List of files to exclude from the preload list generation
     *
     * @var array
     */
    public array $exclude = [];

    /**
     * If the Preloader package should also be included.
     *
     * @var bool
     */
    public bool $includePreloader = false;

    /**
     * Opcache class access
     *
     * @var \DarkGhostHunter\Preloader\Opcache
     */
    protected Opcache $opcache;

    /**
     * ListBuilder constructor.
     *
     * @param  \DarkGhostHunter\Preloader\Opcache $opcache
     */
    public function __construct(Opcache $opcache)
    {
        $this->opcache = $opcache;
    }

    /**
     * Builds a list of scripts
     *
     * @return array
     */
    public function build() : array
    {
        // Exclude the files set by the developer
        $scripts = $this->exclude($this->opcache->getScripts());

        // Sort the scripts by hit ratio
        $scripts = $this->sortScripts($scripts);

        // Cull the list by memory usage
        $scripts = $this->cutByMemoryLimit($scripts);

        // Add files to the preload.
        $scripts = array_merge($scripts, $this->append);

        // Remove duplicates and return
        return array_unique($scripts);
    }

    /**
     * Retrieve a sorted scripts list used by Opcache
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
     * Cuts the files by their cumulative memory consumption
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
     * @param  array $scripts
     * @return array
     */
    protected function exclude(array $scripts)
    {
        return array_diff_key($scripts, array_flip(array_merge($this->exclude, $this->excludedPackageFiles())));
    }

    /**
     * Get the list of files excluded for this package
     *
     * @return array
     */
    protected function excludedPackageFiles()
    {
        return $this->includePreloader
            ? []
            : array_map(fn ($entry) => realpath($entry), glob(realpath(__DIR__ . '/') . '/*.php'));
    }
}
