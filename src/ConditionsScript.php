<?php

namespace DarkGhostHunter\Preloader;

trait ConditionsScript
{
    /**
     * If this Preloader should run
     *
     * @var callable
     */
    protected $condition;

    /**
     * Run the Preloader script when the condition evaluates to true
     *
     * @param  callable  $condition
     * @return $this
     */
    public function when(callable $condition) : self
    {
        $this->condition = $condition;

        return $this;
    }
}
