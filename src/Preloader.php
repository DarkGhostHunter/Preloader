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
     * Stub for the preload file script
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
     * The preload list script location.
     *
     * @var null|string
     */
    protected ?string $output = null;

    /**
     * The class in charge to write the preloader
     *
     * @var \DarkGhostHunter\Preloader\PreloaderCompiler
     */
    protected PreloaderCompiler $compiler;

    /**
     * Script List builder
     *
     * @var \DarkGhostHunter\Preloader\PreloaderLister
     */
    protected PreloaderLister $lister;

    /**
     * Opcache class access
     *
     * @var \DarkGhostHunter\Preloader\Opcache
     */
    protected Opcache $opcache;

    /**
     * Preloader constructor.
     *
     * @param  \DarkGhostHunter\Preloader\PreloaderCompiler $compiler
     * @param  \DarkGhostHunter\Preloader\PreloaderLister $lister
     * @param  \DarkGhostHunter\Preloader\Opcache $opcache
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
    public function list()
    {
        return $this->lister->build();
    }

    /**
     * Use `opcache_compile_file()` to preload each file on the list.
     *
     * @return $this
     */
    public function useCompile()
    {
        $this->compiler->useRequire = false;

        return $this;
    }

    /**
     * Use `require_once $file` to preload each file on the list.
     *
     * @return $this
     */
    public function useRequire()
    {
        $this->compiler->useRequire = true;

        return $this;
    }

    /**
     * Generates a preload script of files.
     *
     * @return bool
     * @throws \LogicException|\RuntimeException
     */
    public function generate()
    {
        if (! $this->canGenerate()) {
            return false;
        }

        $this->compiler->contents = file_get_contents(static::STUB_LOCATION);
        $this->compiler->opcacheConfig = $this->getOpcacheConfig();
        $this->compiler->preloaderConfig = $this->getPreloaderConfig();
        $this->compiler->list = $this->list();

        // @codeCoverageIgnoreStart
        if (! file_put_contents($this->output, $this->compiler->compile(), LOCK_EX)) {
            throw new RuntimeException("Preloader couldn't write the script to [$this->output].");
        }
        // @codeCoverageIgnoreEnd

        return true;
    }

    /**
     * Return if this Preloader can NOT generate the script
     *
     * @return bool
     * @throws \LogicException
     */
    protected function canGenerate()
    {
        if (! $this->output) {
            throw new LogicException('Cannot proceed without pointing where to output the script.');
        }

        if (! $this->compiler->autoload) {
            throw new LogicException('Cannot proceed without a Composer Autoload.');
        }

        if (! file_exists($this->compiler->autoload)) {
            throw new LogicException('Composer Autoload file doesn\'t exists.');
        }

        if (! $this->opcache->isEnabled()) {
            throw new LogicException('Opcache is disabled. No preload script can be generated.');
        }

        if (! $this->opcache->getNumberCachedScripts()) {
            throw new LogicException('Opcache reports 0 cached scripts. No preload will be generated.');
        }

        // We should be able to run the script if the conditions are met and we can write the file
        return $this->shouldRun && $this->shouldWrite();
    }

    /**
     * Creates a new Preloader instance
     *
     * @return static
     */
    public static function make()
    {
        return new static(new PreloaderCompiler, new PreloaderLister($opcache = new Opcache), $opcache);
    }
}
