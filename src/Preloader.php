<?php

namespace DarkGhostHunter\Preloader;

use LogicException;
use RuntimeException;

class Preloader
{
    use Conditions;
    use LimitsList;
    use ManagesFiles;
    use GeneratesScript;

    /**
     * Stub for the preload file script
     *
     * @const
     */
    protected const STUB_LOCATION = __DIR__ . '/preload.php.stub';

    /**
     * Determines if the preload file should be rewritten.
     *
     * @var bool
     */
    protected bool $overwrite = false;

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
     * Determines if the preload file should be rewritten;
     *
     * @param  bool $overwrite
     * @return $this
     */
    public function overwrite(bool $overwrite = true) : self
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Sets the path of the Composer Autoload (usually, "vendor/autoload.php");
     *
     * @param  string $autoload
     * @return $this
     */
    public function autoload(string $autoload) : self
    {
        $this->compiler->autoload = $autoload;

        return $this;
    }

    /**
     * Indicates the preload script output file.
     *
     * @param  string $path
     * @return $this
     */
    public function output(string $path) : self
    {
        $this->output = $this->compiler->output = $path;

        return $this;
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
