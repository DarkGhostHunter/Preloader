<?php

namespace DarkGhostHunter\Preloader;

use const PHP_EOL;

class PreloaderCompiler
{
    /**
     * Preloader stub contents.
     *
     * @var false|string
     */
    public string $contents;

    /**
     * Configuration array.
     *
     * @var array
     */
    public array $preloaderConfig;

    /**
     * Opcache statistics array.
     *
     * @var array
     */
    public array $opcacheConfig;

    /**
     * Mechanism to upload each file into Opcache.
     *
     * @var bool
     */
    public bool $useRequire;

    /**
     * The file list to include.
     *
     * @var array
     */
    public array $list;

    /**
     * The Composer Autoload location.
     *
     * @var null|string
     */
    public ?string $autoloader = null;

    /**
     * PHP.ini preload list input.
     *
     * @var string
     */
    public string $writeTo;

    /**
     * If it should add exception for not-found files.
     *
     * @var bool
     */
    public bool $ignoreNotFound;

    /**
     * Returns a compiled string
     *
     * @return string|string[]
     */
    public function compile() : string
    {
        $replacing = array_merge($this->preloaderConfig, $this->opcacheConfig, [
            '@output'       => $this->scriptRealPath(),
            '@generated_at' => date('Y-m-d H:i:s e'),
            '@autoload'     => isset($this->autoloader)
                ? 'require_once \'' . realpath($this->autoloader) . '\';': null,
            '@list'         => $this->parseList(),
            '@failure'      => $this->ignoreNotFound ? 'continue;' : 'throw new \Exception("{$file} does not exist or is unreadable.");',
            '@mechanism'    => $this->useRequire
                ? 'require_once $file' : 'opcache_compile_file($file)',
        ]);

        return str_replace(array_keys($replacing), $replacing, $this->contents);
    }

    /**
     * Returns the output file real path.
     *
     * @return string
     */
    protected function scriptRealPath()
    {
        // @codeCoverageIgnoreStart
        // We need to get the real path of the preloader file. To do that, we
        // will check if the file already exists, and if not, create a dummy
        // one. If that "touch" fails, we will return whatever path we got.
        if (file_exists($this->writeTo)) {
            return realpath($this->writeTo);
        }

        return $this->touchAndGetRealPath();
        // @codeCoverageIgnoreEnd
    }

    /**
     * Creates a dummy file and returns the real path of it, if possible.
     *
     * @return false|string
     */
    protected function touchAndGetRealPath()
    {
        // @codeCoverageIgnoreStart
        $path = $this->writeTo;

        if (touch($this->writeTo)) {
            $path = $this->writeTo;
            unlink($this->writeTo);
        }

        return $path ?: $this->writeTo;
        // @codeCoverageIgnoreEnd
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
