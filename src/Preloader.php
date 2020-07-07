<?php

namespace DarkGhostHunter\Preloader;

use LogicException;
use RuntimeException;

class Preloader
{
    use ConditionsScript;
    use ManagesFiles;
    use GeneratesScript;

    /**
     * Default memory limit for the preload list.
     *
     * @var float
     */
    public const MEMORY_LIMIT = 32.0;

    /**
     * Stub for the preload file script.
     *
     * @var string
     */
    protected const STUB_LOCATION = __DIR__ . '/preload.php.stub';

    /**
     * Determines if the preload file should be rewritten.
     *
     * @var bool
     */
    protected bool $overwrite = true;

    /**
     * The class in charge to write the preloader.
     *
     * @var \DarkGhostHunter\Preloader\PreloaderCompiler
     */
    protected PreloaderCompiler $compiler;

    /**
     * Script List builder.
     *
     * @var \DarkGhostHunter\Preloader\PreloaderLister
     */
    protected PreloaderLister $lister;

    /**
     * Opcache class access.
     *
     * @var \DarkGhostHunter\Preloader\Opcache
     */
    protected Opcache $opcache;

    /**
     * Preloader constructor.
     *
     * @param  \DarkGhostHunter\Preloader\PreloaderCompiler  $compiler
     * @param  \DarkGhostHunter\Preloader\PreloaderLister  $lister
     * @param  \DarkGhostHunter\Preloader\Opcache  $opcache
     */
    public function __construct(PreloaderCompiler $compiler, PreloaderLister $lister, Opcache $opcache)
    {
        $this->compiler = $compiler;
        $this->lister = $lister;
        $this->opcache = $opcache;
    }

    /**
     * Returns the raw list of files to include in the script
     *
     * @return array
     */
    public function getList() : array
    {
        return $this->prepareLister()->build();
    }

    /**
     * Prepares the Preload Lister.
     *
     * @return \DarkGhostHunter\Preloader\PreloaderLister
     */
    protected function prepareLister()
    {
        $this->lister->list = $this->opcache->getScripts();
        $this->lister->memory = $this->memory;
        $this->lister->selfExclude = $this->selfExclude;
        $this->lister->appended = isset($this->appended) ? $this->getFilesFromFinder($this->appended) : [];
        $this->lister->excluded = isset($this->excluded) ? $this->getFilesFromFinder($this->excluded) : [];

        return $this->lister;
    }

    /**
     * Writes the Preload script to the given path.
     *
     * @param  string  $path
     * @param  bool  $overwrite
     * @return bool
     */
    public function writeTo(string $path, bool $overwrite = true) : bool
    {
        return $this->canGenerate($path, $overwrite) && $this->performWrite($path);
    }

    /**
     * Return if this Preloader can NOT generate the script.
     *
     * @param  string  $path
     * @param  bool  $overwrite
     * @return bool
     */
    protected function canGenerate(string $path, bool $overwrite) : bool
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

    /**
     * Writes the preloader script file into the specified path.
     *
     * @param  string  $path
     * @return false|int
     *
     * @codeCoverageIgnore
     */
    protected function performWrite(string $path)
    {
        if (file_put_contents($path, $this->prepareCompiler($path)->compile(), LOCK_EX)) {
            return true;
        }

        throw new RuntimeException("Preloader couldn't write the script to [$path].");
    }

    /**
     * Prepares the Compiler to make a list.
     *
     * @param  string  $path
     * @return \DarkGhostHunter\Preloader\PreloaderCompiler
     */
    protected function prepareCompiler(string $path)
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

    /**
     * Creates a new Preloader instance
     *
     * @return static
     */
    public static function make() : self
    {
        return new static(new PreloaderCompiler, new PreloaderLister, new Opcache);
    }
}
