<?php

/**
 * This file is a pre-preloader script that includes a shutdown function that enables
 * error logging when something happens while preloading the file list into Opcache.
 * Reference this script in your `php.ini` and add the path to the preload script.
 *
 * For more information:
 * @see https://php.net/manual/en/function.register-shutdown-function.php
 */

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (!$error) {
        return;
    }
    echo 'Preloader Script has stopped with an error:' . \PHP_EOL;
    echo 'Message: ' . $error['message'] . \PHP_EOL;
    echo 'File: ' . $error['file'] . \PHP_EOL;
});

// Uncomment the line below to enable the preloader.
// require_once("/app/vendor/preload.php");
