<?php

namespace Tests;

use Symfony\Component\Finder\Finder;

trait PrepareWorkDir
{
    protected string $workdir;

    protected array $list = [
        'foo.php' => [ // 3
            'hits' => 10,
            'memory_consumption' => 1 * (1024 ** 2),
            'last_used_timestamp' => 1400000000
        ],
        'bar.php' => [ // 1
            'hits' => 20,
            'memory_consumption' => 3 * (1024 ** 2),
            'last_used_timestamp' => 1400000002
        ],
        'quz.php' => [ // 2
            'hits' => 20,
            'memory_consumption' => 5 * (1024 ** 2),
            'last_used_timestamp' => 1400000001
        ],
        'qux.php' => [ // 4
            'hits' => 5,
            'memory_consumption' => 5 * (1024 ** 2),
            'last_used_timestamp' => 1400000010
        ],
        'baz.php' => [ // 5
            'hits' => 5,
            'memory_consumption' => 6 * (1024 ** 2),
            'last_used_timestamp' => 1400000010
        ]
    ];

    protected function setWorkdir()
    {
        $this->workdir = realpath(__DIR__ . '/workdir');
    }

    protected function prepareWorkdir()
    {
        mkdir(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples']));
        mkdir(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a']));
        mkdir(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b']));
        touch(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'foo.php']));
        touch(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'bar.php']));
        touch(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'quz.php']));
        touch(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'qux.php']));
        touch(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'baz.php']));
        touch(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'foo.php']));
        touch(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'bar.php']));
        touch(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'quz.php']));
        touch(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'qux.php']));
        touch(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'baz.php']));
    }

    protected function clearWorkdir()
    {
        if (is_file($preload = implode(DIRECTORY_SEPARATOR, [$this->workdir, 'preloader.php']))) {
            unlink($preload);
        }

        if (is_dir($this->workdir . DIRECTORY_SEPARATOR . 'examples')) {

            foreach ((new Finder())->files()->in($this->workdir . DIRECTORY_SEPARATOR . 'examples') as $file) {
                /** @var \SplFileObject $file */
                unlink($file->getRealPath());
            }

            rmdir(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a']));
            rmdir(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b']));
            rmdir(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples']));
        }
    }
}
