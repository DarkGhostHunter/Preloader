<?php

namespace DarkGhostHunter\Preloader;

trait LimitsList
{
    /**
     * Memory limit (in MB) to constrain the file list.
     *
     * @param  int|float $limit
     * @return $this
     *
     * @throws \RuntimeException
     */
    public function memory($limit) : self
    {
        $this->lister->memory = (float)$limit;

        return $this;
    }
}
