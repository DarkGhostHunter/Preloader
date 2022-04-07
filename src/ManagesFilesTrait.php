<?php

namespace Ninja\Preloader;

use Closure;
use LogicException;
use Symfony\Component\Finder\Finder;

trait ManagesFilesTrait
{
    protected Finder $appended;
    protected Finder $excluded;
    protected bool $selfExclude = false;

    public function append(array|string|Closure|Finder $directories) : self
    {
        $this->appended = $this->findFiles($directories);

        return $this;
    }

    public function exclude(array|string|Closure|Finder $directories) : self
    {
        $this->excluded = $this->findFiles($directories);

        return $this;
    }

    public function selfExclude() : self
    {
        $this->selfExclude = true;

        return $this;
    }

    protected function findFiles(array|string|Closure|Finder $files) : Finder
    {
        if (is_callable($files)) {
            $files($finder = new Finder());
        }
        else {
            $finder = (new Finder())->in($files);
        }

        return $finder->files();
    }

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
