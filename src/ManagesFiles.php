<?php

namespace DarkGhostHunter\Preloader;

use Symfony\Component\Finder\Finder;

trait ManagesFiles
{
    /**
     * Appended file list.
     *
     * @var \Symfony\Component\Finder\Finder
     */
    protected Finder $appended;

    /**
     * Excluded file list.
     *
     * @var \Symfony\Component\Finder\Finder
     */
    protected Finder $excluded;

    /**
     * Append a list of files to the preload list, outside the memory limit.
     *
     * @param  string|array|\Closure  $files
     * @return $this
     */
    public function append($files)
    {
        $this->appended = $this->findFiles($files);

        return $this;
    }

    /**
     * Append a list of files to the preload list, outside the memory limit.
     *
     * @param  string|array|callable  $files
     * @return $this
     */
    public function include($files)
    {
        return $this->append($files);
    }

    /**
     * Exclude a list of files from the preload list generation.
     *
     * @param  string|array|callable  $files
     * @return $this
     */
    public function exclude($files)
    {
        $this->excluded = $this->findFiles($files);

        return $this;
    }

    /**
     * Instantiates the Symfony Finder to look for files.
     *
     * @param  string|array|callable  $files
     * @return \Symfony\Component\Finder\Finder
     */
    protected function findFiles($files) : Finder
    {
        $finder = (new Finder())->files();

        return is_callable($files) ? $files($finder) : $finder->in($files);
    }
}
