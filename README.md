![
Braden Collum - Unsplash (UL) #9HI8UJMSdZA](https://images.unsplash.com/photo-1461896836934-ffe607ba8211?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/preloader.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/preloader) [![License](https://poser.pugx.org/darkghosthunter/preloader/license)](https://packagist.org/packages/darkghosthunter/preloader)
![](https://img.shields.io/packagist/php-v/darkghosthunter/preloader.svg)
 ![](https://github.com/DarkGhostHunter/Preloader/workflows/PHP%20Composer/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Preloader/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Preloader?branch=master)

# Opcache Preloader

Get the best options to keep your application fast as ever, with just one line.

This package generates a [PHP 7.4 preloading](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.preload) script from your Opcache statistics automatically. No need to hack your way in.

> If you're looking for preloading your Laravel project, check [Laraload](https://github.com/DarkGhostHunter/Laraload).

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [How it works](#how-it-works)
- [Configuration](#configuration)
  * [Conditions](#conditions)
    + [`when()`](#when)
  * [List](#list)
    + [`append()`](#append)
    + [`exclude()`](#exclude)
  * [Generation](#generation)
    + [`memoryLimit()`](#memorylimit)
    + [`shouldRequire()`](#shouldrequire) 
  * [Compilation](#compilation)
    + [`writeTo()`](#writeto)
    + [`getFiles()`](#getfiles)
- [Example](#example)
- [Security](#security)
- [License](#license)

## Requirements

* PHP 7.4.3 or later.
* [Opcache enabled](https://www.php.net/manual/en/book.opcache.php) (`ext-opcache`).
* Composer Autoloader (optional).

## Installation

Require this using Composer into your project

    composer require darkghosthunter/preloader

> This package doesn't requires `ext-opcache` to install. Just be sure to have it [enabled in your application server](https://www.php.net/manual/en/book.opcache.php).

## Usage

Anywhere in your application, where Opcache is **enabled** and running, call `Preloader` with where to output the compiled script:

```php
<?php

use DarkGhostHunter\Preloader\Preloader;

Preloader::make()->writeTo(__DIR__.'/preloader.php');
```

This will automatically gather Opcache statistics, and write an optimized `preload.php` file. In this case, the file will be created in the same directory the Preloader was called.

    www
    └── app
        ├── PreloaderCall.php
        └── preload.php

Once generated, tell PHP to use this file as a preloader at startup in your `php.ini`.

```ini
opcache.preload=/www/app/preload.php
```

Restart your PHP process that's using Opcache, and that's all, you're good.

> If you use Preloader when Opcache is disabled or without hits, you will get an Exception.

## How it works

This package asks Opcache for the most requested files and compiles a list from it. You can [check this article in Medium about that preload](https://medium.com/p/9ede756f292c/).

Since the best statistics are those you get **after** your application has been running for a while, you can use your own mechanisms (or the ones provided by the class) to compile the list only after certain conditions are met.

![](https://miro.medium.com/max/1365/1*Zp-rR9-dPNn55L8GjSUpJg.png)

Don't worry, you can configure what and how compile the list.

## Configuration

You can configure the Preloader to run when a condition is met, limit the file list, among what other things.

### Conditions

#### `when()`

This method executes the given callable and checks if the preloader should compile the list or not based on what the callable returning value evaluates.

```php
Preloader::make()->when(fn () => $app->cache()->get('should_run'));
```

### List

#### `append()`

You can add a list of files to the compiled list. These will be appended to the compiled list, and won't account for memory restrictions. 

You can pass an array of files, which is good if you already have a list ready to add.

```php
<?php

use DarkGhostHunter\Preloader\Preloader;

Preloader::make()->append([
    __DIR__ . '/packages/*/**.php', 
    __DIR__ . '/bar.php'
]);
```

> If the files you're adding are already in the compiled list, these will be removed from the included files to avoid effective duplicates.

Alternatively, you can pass a Closure that will receive a Symfony Finder instance to further manipulate the list of files to add.

```php
<?php

use DarkGhostHunter\Preloader\Preloader;
use Symfony\Component\Finder\Finder;

Preloader::make()->append(function (Finder $find) {
    $find->files()->in([
        __DIR__ . '/packages_*/',
        __DIR__ . '/files/',
    ])->name(['*.php', '*.twig']);
});
```

> The `exclude()` method take precedence over `append()`. If you exclude a file that is later appended, you won't exclude it at all.

#### `exclude()`

This method excludes a file or list of files from Opcache list of files, which later end up in the Preload list. Excluding these files will free up the memory of the compiled list, leaving space for others files to be included.

You can pass an array of paths, which is good if you already have a list ready to exclude.
 
```php
<?php

use DarkGhostHunter\Preloader\Preloader;

Preloader::make()->exclude([
    __DIR__ . '/packages/*/**.php', 
    __DIR__ . '/bar.php'
]);
```

Alternatively, you can pass a Closure that will receive a Symfony Finder instance to further manipulate the list of files to exclude.

```php
<?php

use DarkGhostHunter\Preloader\Preloader;
use Symfony\Component\Finder\Finder;

Preloader::make()->exclude(function (Finder $find) {
    $find->files()->in([
        __DIR__ . '/packages_*/',
        __DIR__ . '/files/',
    ])->name(['*.php', '*.twig']);
});
```

### Generation

#### `memoryLimit()`

By default, Preloader defaults a memory limit of 32MB, which is enough for *most* applications. The The Preloader will generate a list of files until that memory limit is reached.

You can set your own memory limit in **MB**. 

```php
<?php

use DarkGhostHunter\Preloader\Preloader;

Preloader::make()->memoryLimit(32);
```

This takes into account the `memory_consumption` key of each script cached in Opcache, not the real file size.

> This limit doesn't have any relation with Opcache memory limit. 

#### `shouldRequire()`

By default, the Preloader will upload the file to Opcache using `opcache_compile_file()`. This avoids executing any file in your project, but no links (traits, interfaces, extended classes, ...) will be resolved from the files compiled.

You can change this using `shouldRequire()` to use the `require_once`, along the path the Composer Autoloader (usually at `vendor/autoload.php`).

```php
<?php

use DarkGhostHunter\Preloader\Preloader;

Preloader::make()->shouldRequire(__DIR__ . '/../vendor/autoload.php');
```

> You may get some warnings when compiling a file with unresolved links. These are not critical, since these files usually are the least requested in your application.

### Compilation

#### `writeTo()`

This will automatically create a PHP-ready script to preload your application. It will return `true` on success, and `false` when the when the conditions are not met.

```php
<?php

use DarkGhostHunter\Preloader\Preloader;

Preloader::make()->writeTo(__DIR__ . '/preloader.php');
```

You can issue `false` as second parameter to not overwrite any existing file in the write path. If a file is found, no preload logic will be run. 

#### `getFiles()`

You can retrieve the raw list of files that should be included as an array using `getFiles()`.

```php
<?php

use DarkGhostHunter\Preloader\Preloader;

Preloader::make()->getFiles();
```

This may become handy if you have your own script, or you just want to tinker around it.

## Example

Okay. Let's say we have a codebase with thousand of files. We don't know any metrics, so we will make generate a preloader script if the request hits the lottery 1 on 100.

```php
<?php
// index.php

use Framework\App;
use DarkGhostHunter\Preloader\Preloader;

require __DIR__ . '/../vendor/autoload.php';

$app = App::make();

$response = $app->run();

$response->sendToBrowser();

Preloader::make()
    ->when(fn() => rand(1, 100) === 50)
    ->memory(256)
    ->writeTo(PHP_LOCALSTATEDIR . '/preload.php') // put it in /var.;
```

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
