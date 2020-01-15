<?php

namespace DarkGhostHunter\Preloader;

use const PHP_EOL;

class PreloaderCompiler
{
    /**
     * Preloader stub contents
     *
     * @var false|string
     */
    public string $contents;

    /**
     * Configuration array
     *
     * @var array
     */
    public array $preloaderConfig;

    /**
     * Opcache statistics array
     *
     * @var array
     */
    public array $opcacheConfig;

    /**
     * Mechanism to upload each file into Opcache.
     *
     * @var bool
     */
    public bool $useRequire = true;

    /**
     * The file list to include
     *
     * @var array
     */
    public array $list;

    /**
     * The Composer Autoload location
     *
     * @var null|string
     */
    public ?string $autoload = null;

    /**
     * PHP.ini preload list input
     *
     * @var string
     */
    public string $output;

    /**
     * Returns a compiled string
     *
     * @return string|string[]
     */
    public function compile() : string
    {
        $replacing = array_merge($this->preloaderConfig, $this->opcacheConfig, [
            '@output' => $this->scriptRealPath(),
            '@generated_at' => date('Y-m-d H:i:s e'),
            '@autoload' => realpath($this->autoload),
            '@list' => $this->parseList(),
            '@mechanism' => $this->useRequire ? 'require_once $file' : 'opcache_compile_file($file)'
        ]);

        return str_replace(array_keys($replacing), $replacing, $this->contents);
    }

    /**
     * Returns the output file real path
     *
     * @return string
     */
    protected function scriptRealPath()
    {
        if ($path = realpath($this->output)) {
            return $path;
        }

        // @codeCoverageIgnoreStart
        // We will try to create a dummy file and just then get the real path of it.
        // After getting the real path, we will delete it and return the path. If
        // we can't, then we will just return the output path string as-it-is.
        if(! touch($this->output)) {
            return $this->output;
        }
        // @codeCoverageIgnoreEnd

        $path = realpath($this->output);

        unlink($this->output);

        return $path;
    }

    /**
     * Parses the list into an injectable string
     *
     * @return string
     */
    protected function parseList()
    {
        return PHP_EOL . '    ' . "'" . implode("'," . PHP_EOL . "    '", $this->list) . "'" . PHP_EOL;
    }
}
