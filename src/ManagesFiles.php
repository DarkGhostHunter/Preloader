<?php

namespace DarkGhostHunter\Preloader;

trait ManagesFiles
{
    /**
     * Include the Preloader files in the file list.
     *
     * @param  bool $include
     * @return $this
     */
    public function includePreloader(bool $include = true)
    {
        $this->lister->includePreloader = $include;

        return $this;
    }

    /**
     * Append a list of files to the preload list. These won't count for file and memory limits.
     *
     * @param $files
     * @return $this
     */
    public function append($files) : self
    {
        $this->lister->append = $this->listFiles((array)$files);

        return $this;
    }

    /**
     * Exclude a list of files from the preload list generation.
     *
     * @param $files
     * @return $this
     */
    public function exclude($files) : self
    {
        $this->lister->exclude = $this->listFiles((array)$files);

        return $this;
    }

    /**
     * take every file string and pass it to the glob function.
     *
     * @param array $files
     * @return array
     */
    protected function listFiles(array $files) : array
    {
        $paths = [];

        // We will cycle trough each "file" and save the resulting array given by glob.
        // If the glob returns false, we will trust the developer goodwill and add it
        // anyway, since the file may not exists until the app generates something.
        foreach ($files as $file) {
            $paths[] = glob($file) ?: [$file];
        }

        return array_merge(...$paths);
    }
}
