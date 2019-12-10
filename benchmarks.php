<?php

define('START', microtime(true));
define('ITERATIONS', $argv[1] ?? 1000);

require_once __DIR__ . '/vendor/autoload.php';

for ($i = 0; $i < ITERATIONS; ++$i)
{
    \DarkGhostHunter\Preloader\Preloader::make()
        ->includePreloader()
        ->autoload(__DIR__ . '/vendor/autoload.php')
        ->overwrite()
//        ->output('php://temp/maxmemory:' . 2 * (1024^2))
        ->output(__DIR__ . '/preload.php')
        ->generate();
}

echo microtime(true) - START;
