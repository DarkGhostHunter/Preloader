<?php

namespace DarkGhostHunter\Preloader;

use Symfony\Component\Finder\Finder;

trait ManagesFiles
{
    /**
     * Appended file list.
     *
     * @var array
     */
    protected $appended;

    /**
     * Excluded file list.
     *
     * @var array
     */
    protected $excluded;

    /**
     * If the package should be excluded from preload list.
     *
     * @var bool
     */
    protected bool $selfExclude = false;

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
     * @param  mixed  $files
     * @return $this
     */
    public function include($files)
    {
        return $this->append($files);
    }

    /**
     * Exclude a list of files from the preload list generation.
     *
     * @param  mixed  $files
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
     * @param  string|array  $files
     * @return array
     */
    protected function findFiles($files) : array
    {
        $finder = $files instanceof Finder ? $files : (new Finder())->in($files);

        return iterator_to_array($finder->files()->getIterator());
    }
}
