<?php

namespace DarkGhostHunter\Preloader;

use LogicException;
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
     * Append a list of directories to the preload list, outside the memory limit.
     *
     * @param  string|array|\Closure|\Symfony\Component\Finder\Finder  $directories
     * @return $this
     */
    public function append($directories) : self
    {
        $this->appended = $this->findFiles($directories);

        return $this;
    }

    /**
     * Exclude a list of directories from the preload list generation.
     *
     * @param  string|array|\Closure|\Symfony\Component\Finder\Finder  $directories
     * @return $this
     */
    public function exclude($directories) : self
    {
        $this->excluded = $this->findFiles($directories);

        return $this;
    }

    /**
     * Excludes the package files from the preload list.
     *
     * @return $this
     */
    public function selfExclude() : self
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
            $files($finder = new Finder());
        }
        else {
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

        try {
            foreach ($finder as $file) {
                $paths[] = $file->getRealPath();
            }

            return $paths;
        }
        // If the developer used an empty array for the Finder instance, we will just
        // catch the exception thrown by having no directories to find and return an
        // empty array. Otherwise the Preloader will fail when retrieving the list.
        catch (LogicException $exception) {
            return $paths;
        }
    }
}
