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
     * Stub for the preload file script.
     *
     * @var string
     */
    protected const STUB_LOCATION = __DIR__ . '/preload.php.stub';

    /**
     * Default memory limit for the preload list.
     *
     * @var float
     */
    protected const MEMORY_LIMIT = 32.0;

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
    public function getList()
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
        $this->lister->selfExclude = $this->selfExclude;
        $this->lister->appended = $this->appended ? iterator_to_array($this->appended->getIterator()) : [];
        $this->lister->excluded = $this->excluded ? iterator_to_array($this->excluded->getIterator()) : [];

        return $this->lister;
    }

    /**
     * Writes the Preload script to the given path.
     *
     * @param  string  $path
     * @return bool
     */
    public function writeTo(string $path)
    {
        if (! $this->canGenerate()) {
            return false;
        }

        // @codeCoverageIgnoreStart
        if (! file_put_contents($path, $this->prepareCompiler()->compile(), LOCK_EX)) {
            throw new RuntimeException("Preloader couldn't write the script to [$this->output].");
        }
        // @codeCoverageIgnoreEnd

        return true;
    }

    /**
     * Prepares the Compiler to make a list.
     *
     * @return \DarkGhostHunter\Preloader\PreloaderCompiler
     */
    protected function prepareCompiler()
    {
        $this->compiler->contents = file_get_contents(static::STUB_LOCATION);
        $this->compiler->opcacheConfig = $this->getOpcacheConfig();
        $this->compiler->preloaderConfig = $this->getPreloaderConfig();
        $this->compiler->list = $this->getList();

        return $this->compiler;
    }

    /**
     * Return if this Preloader can NOT generate the script
     *
     * @return bool
     * @throws \LogicException
     */
    protected function canGenerate()
    {
        if (! $this->useRequire && (!$this->autoloader || ! file_exists($this->autoloader))) {
            throw new LogicException('Cannot proceed without a Composer Autoload.');
        }

        if (! $this->opcache->isEnabled()) {
            throw new LogicException('Opcache is disabled. No preload script can be generated.');
        }

        if (! $this->opcache->getNumberCachedScripts()) {
            throw new LogicException('Opcache reports 0 cached scripts. No preload can be generated.');
        }

        // We should be able to run the script if the conditions are met and we can write the file
        return $this->condition ? call_user_func($this->condition) : true;
    }

    /**
     * Creates a new Preloader instance
     *
     * @return static
     */
    public static function make()
    {
        return new static(new PreloaderCompiler, new PreloaderLister, new Opcache);
    }
}
