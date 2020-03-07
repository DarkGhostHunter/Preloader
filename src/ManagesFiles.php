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
     * If the package should be excluded from preload list.
     *
     * @var bool
     */
    protected bool $selfExclude = false;

    /**
     * Append a list of files to the preload list, outside the memory limit.
     *
     * @param  string|array|\Closure|\Symfony\Component\Finder\Finder  $files
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
     * @param  string|array|\Closure|\Symfony\Component\Finder\Finder  $files
     * @return $this
     */
    public function include($files)
    {
        return $this->append($files);
    }

    /**
     * Exclude a list of files from the preload list generation.
     *
     * @param  string|array|\Closure|\Symfony\Component\Finder\Finder  $files
     * @return $this
     */
    public function exclude($files)
    {
        $this->excluded = $this->findFiles($files);

        return $this;
    }

    /**
     * Excludes the package files from the preload list.
     *
     * @return $this
     */
    public function selfExclude()
    {
        $this->selfExclude = true;

        return $this;
    }

    /**
     * Instantiates the Symfony Finder to look for files.
     *
     * @param  string|array|\Closure|\Symfony\Component\Finder\Finder  $files
     * @return \Symfony\Component\Finder\Finder
     */
    protected function findFiles($files) : Finder
    {
        if (is_callable($files)) {
            $finder = $files(new Finder());
        } elseif ($files instanceof Finder) {
            $finder = $files;
        } else {
            $finder = (new Finder())->in($files);
        }

        return $finder->files();
    }

    /**
     * Return an array of the files from the Finder.
     *
     * @param  \Symfony\Component\Finder\Finder  $finder
     * @return array
     */
    protected function getFilesFromFinder(Finder $finder) : array
    {
        $paths = [];

        foreach ($finder as $file) {
            $paths = $file->getRealPath();
        }

        return $paths;
    }
}
