<?php

namespace Ninja\Preloader;

use const PHP_EOL;

class PreloaderCompiler
{
    public string $contents;
    public array $preloaderConfig;
    public array $opcacheConfig;
    public bool $useRequire;
    public array $list;
    public ?string $autoloader = null;
    public string $writeTo;
    public bool $ignoreNotFound;

    public function compile(): string
    {
        $replacing = array_merge($this->preloaderConfig, $this->opcacheConfig, [
            '@output'       => $this->scriptRealPath(),
            '@generated_at' => date('Y-m-d H:i:s e'),
            '@autoload'     => isset($this->autoloader)
                ? 'require_once \'' . realpath($this->autoloader) . '\';' : null,
            '@list'         => $this->parseList(),
            '@failure'      => $this->ignoreNotFound ?
                'continue;' :
                'throw new \Exception("{$file} does not exist or is unreadable.");',
            '@mechanism'    => $this->useRequire
                ? 'require_once $file' : 'opcache_compile_file($file)',
        ]);

        return str_replace(array_keys($replacing), $replacing, $this->contents);
    }

    protected function scriptRealPath(): string
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

    protected function touchAndGetRealPath(): string
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

    protected function parseList(): string
    {
        return PHP_EOL . '    ' . "'" . implode("'," . PHP_EOL . "    '", $this->list) . "'" . PHP_EOL;
    }
}
