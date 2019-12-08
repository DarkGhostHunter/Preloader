<?php

namespace DarkGhostHunter\Preloader;

trait LimitsList
{
    /**
     * Memory limit (in MB) to constrain the file list. Minimum is 1.
     *
     * @param  int $limit
     * @return $this
     *
     * @throws \RuntimeException
     */
    public function memory(int $limit) : self
    {
        $this->lister->memory = $limit;

        return $this;
    }
}
