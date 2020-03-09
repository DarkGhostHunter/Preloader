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

    /**
     * Run the Preloader script one in a given number of random chances.
     *
     * @param  int  $chances
     * @return $this
     */
    public function whenOneIn(int $chances) : self
    {
        return $this->when(function () use ($chances) {
            return random_int(1, $chances) === (int)ceil($chances/2);
        });
    }
}
