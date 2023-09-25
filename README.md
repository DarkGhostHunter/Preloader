![
Braden Collum - Unsplash (UL) #9HI8UJMSdZA](https://images.unsplash.com/photo-1461896836934-ffe607ba8211?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/diego-ninja/preloader.svg?style=flat-square)](https://packagist.org/packages/diego-ninja/preloader) [![License](https://poser.pugx.org/darkghosthunter/preloader/license)](https://packagist.org/packages/darkghosthunter/preloader)
![](https://img.shields.io/packagist/php-v/diego-ninja/preloader.svg)
 ![](https://github.com/diego-ninja/Preloader/workflows/PHP%20Composer/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/diego-ninja/Preloader/badge.svg?branch=master)](https://coveralls.io/github/diego-ninja/Preloader?branch=master)

# Opcache Preloader

Get the best options to keep your application fast as ever, with just one line.

This package generates a [PHP preloading](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.preload) script from your Opcache statistics automatically. No need to hack your way in. This package is a fork of
[darkghosthunter/preloader](https://github.com/DarkGhostHunter/Preloader) with the only target of update code and dependencies to fit with php 8.x and Symfony 6 components. It doesn't add, at least at the moment, new functionality.

If you need php 7.4 support please, use the aforementioned package.

> If you're looking for preloading your Laravel project, check [Laragear PReload](https://github.com/Laragear/Preload).

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [How it works](#how-it-works)
- [Configuration](#configuration)
  * [Conditions](#conditions)
    + [`when()`](#when)
    + [`whenOneIn()`](#whenOneIn)
  * [Listing](#listing)
    + [`append()`](#append)
    + [`exclude()`](#exclude)
    + [`selfExclude()`](#selfexclude)
  * [Generation](#generation)
    + [`memoryLimit()`](#memorylimit)
    + [`useRequire()`](#userequire)
    + [`ignoreNotFound()`](#ignorenotfound)
  * [Compilation](#compilation)
    + [`writeTo()`](#writeto)
    + [`getList()`](#getlist)
- [Safe Preloader](#safe-preloader)
- [Example](#example)
- [Security](#security)
- [License](#license)

## Requirements

* PHP 8.0 or later.
* [Opcache & Preloading enabled](https://www.php.net/manual/en/book.opcache.php) (`ext-opcache`).
* Composer Autoloader (optional).

## Installation

Require this using Composer into your project

    composer require diego-ninja/preloader

> This package doesn't require `ext-opcache` to install. Just be sure to have it [enabled in your application server](https://www.php.net/manual/en/book.opcache.php).

## Usage

Anywhere in your application, where Opcache is **enabled** and running, call `Preloader` with where to output the compiled script:

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->writeTo(__DIR__.'/preloader.php');
```

This will automatically gather Opcache statistics, and write an optimized `preload.php` file. In this case, the file will be created in the same directory the Preloader was called.

    www
    └── app
        ├── PreloaderCall.php
        └── preload.php

Once generated, tell PHP to use this file as a preloader at start up in your `php.ini`.

```ini
opcache.preload=/www/app/preload.php
```

Once the script is generated, **you're encouraged to restart your PHP process** (or server, in some cases) to pick up the generated preload script. Only generating the script [is not enough](https://www.php.net/manual/en/opcache.preloading.php).

> If you use Preloader when Opcache is disabled or without hits, you will get an Exception.

## How it works

This package asks Opcache only for the most requested files and compiles a list from it. You can [check this article in Medium about that preload](https://medium.com/p/9ede756f292c/).

Since the best statistics are those you get **after** your application has been running for a while, you can use your own mechanisms to compile the list only after certain conditions are met.

![](https://miro.medium.com/max/1365/1*Zp-rR9-dPNn55L8GjSUpJg.png)

Don't worry, you can configure what and how compile the list.

## Configuration

You can configure the Preloader to run when a condition is met, limit the file list, among what other things.

### Conditions

#### `when()`

This method executes the given callable and checks if the preloader should compile the list or not based on what the callable returning value evaluates.

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->when(fn () => $app->cache()->get('should_run'));
```

This is handy if you can combine the condition with your own application logic, like a given number of requests, or an external signal.

#### `whenOneIn()`

This is method is just a helper to allows you to quickly generate a Preloader script in one of a given number of random chances.

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->whenOneIn(50);
```

For example, the above makes the Preloader generate a compiled list one in fifty chances.

### Listing

#### `append()`

You can add a list of directories to the compiled list. The files inside them will be appended to the compiled list, and won't account for memory restrictions.

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->append([
    __DIR__ . '/files/*/more_files', 
    __DIR__ . '/classes/'
]);
```

> If the files you're adding are already in the compiled list, these will be removed from the included files to avoid effective duplicates.

This packages includes [Symfony Finder](https://symfony.com/doc/current/components/finder.html), so as an alternative you can pass a closure that receives the Finder instance along with the files you want to append.

```php
<?php

use Symfony\Component\Finder\Finder;
use Ninja\Preloader\Preloader;

Preloader::make()->append(function (Finder $find) {
    $find->files()->in('/package')->name(['*.php', '*.twig']);
});
```

> The `exclude()` method take precedence over `append()`. If you exclude a file that is later appended, you won't exclude it at all.

#### `exclude()`

This method excludes files from inside directories from Opcache list, which later end up in the Preload list. Excluding files may free up the memory of the compiled list, leaving space for others to be included.

You can pass an array of paths, which is good if you already have a list ready to exclude.

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->exclude([
    __DIR__ . '/files/*/more_files',
    __DIR__ . '/vendor'
]);
```

This packages includes [Symfony Finder](https://symfony.com/doc/current/components/finder.html), so as an alternative you can pass a Closure receiving the Finder instance along with the files you want to exclude.

```php
<?php

use Symfony\Component\Finder\Finder;
use Ninja\Preloader\Preloader;

Preloader::make()->exclude(function (Finder $find) {
    $find->files()->in('/package')->name(['*.php', '*.twig']);
});
```

> The `exclude()` method take precedence over `append()`. If you exclude a file that is later appended, you won't exclude it at all.

#### `selfExclude()`

Automatically excludes the Package files from the Preload list.

By default, the package is not excluded, since it may be part of the most requested files. It's recommended to exclude the package only if you have total confidence it won't be called once Opcache Preloading is enabled.

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->selfExclude();
```

### Generation

#### `memoryLimit()`

By default, Preloader defaults a memory limit of 32MB, which is enough for *most* applications. The Preloader will generate a list of files until that memory limit is reached.

You can set your own memory limit in **MB**.

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->memoryLimit(32);
```

This takes into account the `memory_consumption` key of each script cached in Opcache, not the real file size.

> This limit doesn't have any relation with Opcache memory limit. 

To disable the limit, use `memoryLimit(0)`. This will list **ALL** available files from Opcache.

#### `useRequire()`

By default, the Preloader will upload the files to Opcache using `opcache_compile_file()`. This avoids executing any file in your project, but no links (traits, interfaces, extended classes, ...) will be resolved from the files compiled. You may have some warnings of unresolved links when preloading (nothing too dangerous).

You can change this using `useRequire()`, which changes to  `require_once`, along the path the Composer Autoloader (usually at `vendor/autoload.php`) to resolve the links.

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->useRequire(__DIR__ . '/../vendor/autoload.php');
```

> You may get some warnings when compiling a file with unresolved links. These are not critical, since these files usually are the least requested in your application.

#### `ignoreNotFound()`

Some applications may create files at runtime that are genuinely cached by Opcache, but doesn't exist when the application is firstly deployed.

To avoid this problem, you can use the `ignoreNotFound()`, which will compile a script that ignore files not found or are unreadable.

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->ignoreNotFound();
```

> If the file is readable but its preloading returns an error, it will throw an exception nonetheless.

### Compilation

#### `writeTo()`

This will automatically create a PHP-ready script to preload your application. It will return `true` on success, and `false` when the when the conditions are not met.

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->writeTo(__DIR__ . '/preloader.php');
```

You can issue `false` as second parameter to not overwrite any existing file in the write path. If a file is found, no preload logic will be run. 

#### `getList()`

You can retrieve the raw list of files that should be included as an array using `getList()`.

```php
<?php

use Ninja\Preloader\Preloader;

Preloader::make()->getList();
```

This may become handy if you have your own script, or you just want to tinker around it.

## Safe Preloader

This packages comes with a handy Safe Preloader, located in `helpers/safe_preloader.php`.

What it does is very simple: it registers a shutdown function for PHP that is executed after the preload script finishes, and registers any error the script may have returned so you can debug it.

To use it, copy the file into an accessible path for PHP, and along with the real preloader script, reference it in your `php.ini`:

```ini
opcache.preload=/www/app/safe_preloader.php
```

```php
<?php
// /www/app/safe_preloader.php

register_shutdown_function(function (): void {
    $error = error_get_last();
    if (!$error) {
        return;
    }
    echo 'Preloader Script has stopped with an error:' . \PHP_EOL;
    echo 'Message: ' . $error['message'] . \PHP_EOL;
    echo 'File: ' . $error['file'] . \PHP_EOL;
});

// require_once /* Path to your preload script */
require_once '/www/app/preloader.php';
```

Technically speaking, the Opcache preloads the files in a different process, so there shouldn't be a problem using this safe-preloader. 

## Example

Okay. Let's say we have a codebase with thousand of files. We don't know any metrics, so we will generate a preloader script if the request hits the lottery 1 on 100, with a memory limit of 64MB.

```php
<?php
// index.php

use Framework\App;
use Ninja\Preloader\Preloader;

require __DIR__ . '/../vendor/autoload.php';

$app = App::make();

$response = $app->run();

$response->sendToBrowser();

Preloader::make()
    ->whenOneIn(100)
    ->memoryLimit(64)
    ->writeTo(PHP_LOCALSTATEDIR . '/preload.php'); // put it in /var.;
```

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
