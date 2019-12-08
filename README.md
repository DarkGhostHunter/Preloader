![
Braden Collum - Unsplash (UL) #9HI8UJMSdZA](https://images.unsplash.com/photo-1461896836934-ffe607ba8211?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/preloader.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/preloader) [![License](https://poser.pugx.org/darkghosthunter/preloader/license)](https://packagist.org/packages/darkghosthunter/preloader)
![](https://img.shields.io/packagist/php-v/darkghosthunter/preloader.svg)
 ![](https://github.com/DarkGhostHunter/Preloader/workflows/PHP%20Composer/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Preloader/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Preloader?branch=master)

# Opcache Preloader

Get the best options to keep your application fast as ever, with just one line.

This package generates a [PHP 7.4 preloading](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.preload) script from your Opcache statistics automatically. No need to hack your way in.

## Installation

Require this using Composer into your project

    composer require darkghosthunter/preloader

> This package doesn't requires `ext-opcache` to install. Just be sure to have it [enabled in your application server](https://www.php.net/manual/en/book.opcache.php).

## Usage

Anywhere in your application, where Opcache is **enabled** and running, call `Preloader` with the path of your Composer Autoloader, and where to output the compiled script:

```php
<?php

use DarkGhostHunter\Preloader\Preloader;

Preloader::make()->autoload(__DIR__ . '/vendor/autoloader.php')
    ->output(__DIR__.'/preloader.php')
    ->generate();
```
 
This will automatically gather Opcache statistics, and write an optimized `preload.php` file. In this case, the file will be created in the same directory the Preloader was called.

    www
    └── app
        ├── PreloaderCall.php
        └── preload.php

Then, tell PHP to use this file as a preloader at startup in your `php.ini`.

```ini
opcache.preload=/www/app/preload.php
```

Restart your PHP process using Opcache and that's all, you're good.

> If you use Preloader when Opcache is disabled or without hits, you will get an Exception.

## How it works

This package will ask Opcache for statistics about what files are the most requested.

Since the best statistics are those you get after your application has been running for a while, you can use your own mechanisms (or the ones provided by the class) to compile the list only after certain conditions are met.

Don't worry, you can configure what and how compile the list.

## Configuration

Yuo can configure the Preloader to run when a condition is met, limit the file list, and where to output the compiled preload list.

After the Opcache hits reach a certain number, the Preloader will generate the script, not before.

### `when()` (optional)

If you don't feel like using Opcache hits, you can just use `when()`. The Preloader will proceed when the variable being passed evaluates to `true`, which will be in your hands.

    Preloader::make()->when(false); // You will never run, ha ha ha!

You can also use a Closure (Arrow function or any other callable) that returns `true`.

    Preloader::make()->when(fn () => $app->cache()->get('should_run'));


#### `whenHits()` (optional)

This is the best way to gather good statistics for a good preloading list if you don't know the real load of your application.

    Preloader::make()->whenHits(200000): // After a given number of hits.

#### `whenOneIn()` (optional)

This is another helper for conditioning the generation. The list will be generated one in a given number of chances (the higher is it, the more rare till be). 

    Prealoder::make()->whenOneIn(2000); // 1 in 2,000 chances.

This may come in handy using it with `overwrite()` to constantly recreate the list.

    Prealoder::make()->overwrite()->whenOneIn(2000);

### `memory()` (optional, default)

    Preloader::make()->memory(32);

Set your memory limit in **MB**. The default of 32MB is enough for *most* applications. The Preloader will generate a list of files until that memory limit is reached.

This takes into account the `memory_consumption` key of each script cached in Opcache, not the real file size.

> This limit doesn't have any relation with Opcache memory limit. 

### `exclude()` (optional)

You can exclude from the list given by Opcache using `exclude()`, which accepts a single file or an array of files.

    Preloader::make()->exclude(['foo.php', 'bar.php']);

These excluded files will be excluded from the list generation, also excluding them from memory limits.

> Preloader library files are automatically excluded.

### `append()` (optional)

    Preloader::make()->append(['foo.php', 'bar.php']);

Of course you can add files using absolute paths manually to the preload script to be generated. Just issue them with `append()`.

Prepending files will put them **after** the list generation, so they won't count list or memory limits.

    Preloader::make()->top(0.5)->memory(64)->append('foo.bar');

> Any duplicated file appended will be ignored since the list will remove them automatically before compiling the script. 

### `output()` (required)

We need to know where to output the script. It's recommended to do it in the same application folder, since most PHP processes will have access to write in it. If not, you're free to point out where.

    Preloader::make()->output(__DIR__ . '/../../my-preloader.php'); 

### `overwrite()` (optional)

Sometimes you may have run your preloader script already. To avoid replacing the list with another one, Preloader by default doesn't do nothing when it detects the file already exists.

To change this behaviour, you can use the `overwrite()` method to instruct Preloader to always rewrite the file.

    Preloader::make()->overwrite()->generate();

> Watch out using this along conditions like `whenHits()` and `when()`. If the condition are true, the Preloader will overwrite the preload script... over and over and over again! 

### `generate()` (required)

Once your Preloader configuration is ready, you can generate the list using `generate()`.

    Preloader::make()->generate();

This will automatically create a PHP-ready script to preload your application. It will return `true` on success, and `false` when the when the conditions are not met or an existing preload file exists.

## Give me an example

Okay. Let's say we have a framework with 1500 files. We don't know any metrics, so we will blindly push all files that we can inside 256MB of memory after a week has passed after deployment, so it can have enough Opcache statistics.

```php
<?php
// index.php

require __DIR__ . '/../vendor/autoload.php';

$app = \Framework\App::make();

$response = $app->run();

$response->sendToBrowser();

// A week after deployment
$weekAfterDeploy = $app->deploymentTimestamp() + (7*24*60*60);

// If a week has passed, and no script was created, do it!
\DarkGhostHunter\Preloader\Preloader::make()
    ->when(time() > $weekAfterDeploy)
    ->autoload(__DIR__ , '/../vendor/autoload.php')
    ->memory(256) // 256MB of memory limit
    ->output(PHP_LOCALSTATEDIR . '/preload.php') // put it in /var.
    ->generate();
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
