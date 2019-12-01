<?php

namespace DarkGhostHunter\Preloader;

trait ManagesFiles
{
    /**
     * Append a list of files to the preload list. These won't count for file and memory limits.
     *
     * @param $files
     * @return $this
     */
    public function append($files) : self
    {
        $this->lister->append = (array)$files;

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
        $this->lister->exclude = (array)$files;

        return $this;
    }
}
