<?php

namespace Ninja\Preloader;

use LogicException;
use RuntimeException;

class Preloader
{
    use ConditionsScriptTrait;
    use ManagesFilesTrait;
    use GeneratesScriptTrait;

    public const MEMORY_LIMIT = 32.0;
    protected const STUB_LOCATION = __DIR__ . '/preload.php.stub';

    protected bool $overwrite = true;

    public function __construct(
        protected PreloaderCompiler $compiler,
        protected PreloaderLister $lister,
        protected Opcache $opcache
    ) {
    }

    public function getList(): array
    {
        return $this->prepareLister()->build();
    }

    protected function prepareLister(): PreloaderLister
    {
        $this->lister->list = $this->opcache->getScripts();
        $this->lister->memory = $this->memory;
        $this->lister->selfExclude = $this->selfExclude;
        $this->lister->appended = isset($this->appended) ? $this->getFilesFromFinder($this->appended) : [];
        $this->lister->excluded = isset($this->excluded) ? $this->getFilesFromFinder($this->excluded) : [];

        return $this->lister;
    }

    public function writeTo(string $path, bool $overwrite = true): bool
    {
        return $this->canGenerate($path, $overwrite) && $this->performWrite($path);
    }

    protected function canGenerate(string $path, bool $overwrite): bool
    {
        // When using require, check if the autoloader exists.
        if ($this->useRequire && ! file_exists($this->autoloader)) {
            throw new LogicException('Cannot proceed without a Composer Autoload.');
        }

        // Bail out if Opcache is not enabled.
        if (! $this->opcache->isEnabled()) {
            throw new LogicException('Opcache is disabled. No preload script can be generated.');
        }

        // Also bail out if Opcache doesn't have any cached script.
        if (! $this->opcache->getNumberCachedScripts()) {
            throw new LogicException('Opcache reports 0 cached scripts. No preload can be generated.');
        }

        // We can't generate a script if we can't overwrite an existing file.
        if (! ($this->overwrite = $overwrite) && file_exists($path)) {
            throw new LogicException('Preloader script already exists in the given path.');
        }

        // If there is a condition, call it.
        if ($this->condition) {
            return (bool) call_user_func($this->condition);
        }

        return true;
    }

    protected function performWrite(string $path): bool
    {
        if (file_put_contents($path, $this->prepareCompiler($path)->compile(), LOCK_EX)) {
            return true;
        }

        throw new RuntimeException("Preloader couldn't write the script to [$path].");
    }

    protected function prepareCompiler(string $path): PreloaderCompiler
    {
        $this->compiler->list = $this->getList();
        $this->compiler->autoloader = $this->autoloader;
        $this->compiler->contents = file_get_contents(static::STUB_LOCATION);
        $this->compiler->opcacheConfig = $this->getOpcacheConfig();
        $this->compiler->preloaderConfig = $this->getPreloaderConfig();
        $this->compiler->useRequire = $this->useRequire;
        $this->compiler->ignoreNotFound = $this->ignoreNotFound;
        $this->compiler->autoloader = $this->autoloader;
        $this->compiler->writeTo = $path;

        return $this->compiler;
    }

    public static function make(): self
    {
        return new static(new PreloaderCompiler(), new PreloaderLister(), new Opcache());
    }
}
