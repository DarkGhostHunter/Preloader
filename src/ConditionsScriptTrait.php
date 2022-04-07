<?php

namespace Ninja\Preloader;

trait ConditionsScriptTrait
{
    protected mixed $condition = null;

    public function when(callable $condition) : self
    {
        $this->condition = $condition;

        return $this;
    }

    public function whenOneIn(int $chances) : self
    {
        return $this->when(function () use ($chances) {
            return random_int(1, $chances) === (int)ceil($chances/2);
        });
    }
}
