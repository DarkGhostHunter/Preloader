<?php

namespace Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use DarkGhostHunter\Preloader\Opcache;
use DarkGhostHunter\Preloader\Preloader;
use DarkGhostHunter\Preloader\PreloaderLister;
use DarkGhostHunter\Preloader\PreloaderCompiler;
use const DIRECTORY_SEPARATOR;

class PreloaderTest extends TestCase
{
    use PrepareWorkDir;

    /** @var \DarkGhostHunter\Preloader\Opcache|\PHPUnit\Framework\MockObject\MockObject  */
    protected $opcache;

    protected $preloaderPath;

    protected function setUp() : void
    {
        parent::setUp();

        $this->opcache = $this->createMock(Opcache::class);

        $this->setWorkdir();
        $this->clearWorkDir();
        $this->prepareWorkdir();

        $this->preloaderPath = $this->workdir . DIRECTORY_SEPARATOR . 'preloader.php';
    }

    protected function mockOpcache($list = null)
    {
        $this->opcache->method('isEnabled')
            ->willReturn(true);
        $this->opcache->method('getNumberCachedScripts')
            ->willReturn(1000);
        $this->opcache->method('getHits')
            ->willReturn(1001);
        $this->opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => $usedMemory = rand(1000, 999999),
                    'free_memory' => $freeMemory = rand(1000, 999999),
                    'wasted_memory' => $wastedMemory = rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => $cachedScripts = rand(1000, 999999),
                    'opcache_hit_rate' => $hitRate = rand(100, 9999)/100,
                    'misses' => $misses = rand(1000, 999999),
                ],
            ]);
        $this->opcache->method('getScripts')
            ->willReturn($list ?? $this->list);
    }

    public function test_when_condition_receives_callable_and_resolves_on_generation()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $resolved = false;

        $preloader->when(function () use (&$resolved) {
            return $resolved = true;
        });

        $this->assertFalse($resolved);

        $this->assertTrue($preloader->writeTo($this->preloaderPath));

        $this->assertTrue($resolved);
    }

    public function test_when_one_in_condition_receives_callable_and_resolves_on_generation()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->whenOneIn(10);

        do {
            $write = $preloader->writeTo($this->preloaderPath);
            if (!$write) {
                $this->assertFalse($write);
            }
        } while (!$write);

        $this->assertTrue($write);
    }

    public function test_append_files_as_array()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->append([
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a']),
        ]);

        $this->assertTrue($preloader->writeTo($this->preloaderPath));

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'foo.php']), $contents
        );
        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'bar.php']), $contents
        );
        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'quz.php']), $contents
        );
        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'qux.php']), $contents
        );
        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'baz.php']), $contents
        );
    }

    public function test_append_files_as_callable()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->append(function (Finder $find) {
            return $find->in(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b']));
        });

        $this->assertTrue($preloader->writeTo($this->preloaderPath));

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'foo.php']), $contents
        );
        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'bar.php']), $contents
        );
        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'quz.php']), $contents
        );
        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'qux.php']), $contents
        );
        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'baz.php']), $contents
        );
    }

    public function test_exclude_files_as_array()
    {
        $this->mockOpcache([
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'foo.php']) => [ // 3
                'hits' => 10,
                'memory_consumption' => 1 * (1024 ** 2),
                'last_used_timestamp' => 1400000000
            ],
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'bar.php']) => [ // 1
                'hits' => 20,
                'memory_consumption' => 3 * (1024 ** 2),
                'last_used_timestamp' => 1400000002
            ],
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'qux.php']) => [ // 1
                'hits' => 20,
                'memory_consumption' => 3 * (1024 ** 2),
                'last_used_timestamp' => 1400000002
            ],
        ]);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->exclude([
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b'])
        ]);

        $this->assertTrue($preloader->writeTo($this->preloaderPath));

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'foo.php']), $contents
        );

        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'bar.php']), $contents
        );

        $this->assertStringNotContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'qux.php']), $contents
        );
    }

    public function test_exclude_files_as_closure()
    {
        $this->mockOpcache([
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'foo.php']) => [ // 3
                'hits' => 10,
                'memory_consumption' => 1 * (1024 ** 2),
                'last_used_timestamp' => 1400000000
            ],
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'bar.php']) => [ // 1
                'hits' => 20,
                'memory_consumption' => 3 * (1024 ** 2),
                'last_used_timestamp' => 1400000002
            ],
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'qux.php']) => [ // 1
                'hits' => 20,
                'memory_consumption' => 3 * (1024 ** 2),
                'last_used_timestamp' => 1400000002
            ],
        ]);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->exclude(function (Finder $find) {
            $find->in(implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b']));
        });

        $this->assertTrue($preloader->writeTo($this->preloaderPath));

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'foo.php']), $contents
        );

        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'bar.php']), $contents
        );

        $this->assertStringNotContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'qux.php']), $contents
        );
    }

    public function test_self_excludes_from_list()
    {
        $this->mockOpcache([
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'foo.php']) => [ // 3
                'hits' => 10,
                'memory_consumption' => 1 * (1024 ** 2),
                'last_used_timestamp' => 1400000000
            ],
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'bar.php']) => [ // 1
                'hits' => 20,
                'memory_consumption' => 3 * (1024 ** 2),
                'last_used_timestamp' => 1400000002
            ],
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'qux.php']) => [ // 1
                'hits' => 20,
                'memory_consumption' => 3 * (1024 ** 2),
                'last_used_timestamp' => 1400000002
            ],
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'quz.php']) => [ // 1
                'hits' => 20,
                'memory_consumption' => 3 * (1024 ** 2),
                'last_used_timestamp' => 1400000002
            ],
            realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'Opcache.php'])) => [ // 1
                'hits' => 20,
                'memory_consumption' => 3 * (1024 ** 2),
                'last_used_timestamp' => 1400000002
            ],
            realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'Preloader.php'])) => [ // 1
                'hits' => 20,
                'memory_consumption' => 3 * (1024 ** 2),
                'last_used_timestamp' => 1400000002
            ],
        ]);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $this->assertTrue($preloader->selfExclude()->writeTo($this->preloaderPath));

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'foo.php']), $contents
        );

        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_a', 'bar.php']), $contents
        );

        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'quz.php']), $contents
        );

        $this->assertStringContainsString(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'examples', 'test_b', 'qux.php']), $contents
        );

        $this->assertStringNotContainsString(
            realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'Preloader.php'])),
            $contents
        );

        $this->assertStringNotContainsString(
            realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'Opcache.php'])),
            $contents
        );
    }

    public function test_limits_memory_list()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->memoryLimit(10)->writeTo($this->preloaderPath);

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringContainsString('Memory limit: 10 MB', $contents);
        $this->assertStringContainsString('bar.php', $contents);
        $this->assertStringContainsString('quz.php', $contents);
        $this->assertStringContainsString('foo.php', $contents);
        $this->assertStringNotContainsString('qux.php', $contents);
        $this->assertStringNotContainsString('baz.php', $contents);
    }

    public function test_memory_limit_disabled_lists_all_files()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->memoryLimit(0)->writeTo($this->preloaderPath);

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringContainsString('Memory limit: (disabled)', $contents);
        $this->assertStringContainsString('bar.php', $contents);
        $this->assertStringContainsString('quz.php', $contents);
        $this->assertStringContainsString('foo.php', $contents);
        $this->assertStringContainsString('qux.php', $contents);
        $this->assertStringContainsString('baz.php', $contents);
    }

    public function test_uses_compile_by_default()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->writeTo($this->preloaderPath);

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringContainsString('opcache_compile_file($file)', $contents);
        $this->assertStringNotContainsString('autoload', $contents);
    }

    public function test_uses_require_instead_of_compile()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->useRequire(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'autoload.php'])
        )->writeTo($this->preloaderPath);

        $contents = file_get_contents($this->preloaderPath);

        $path = realpath(implode(DIRECTORY_SEPARATOR, [
            $this->workdir, 'autoload.php'
        ]));
        $expectedRequire = "require_once '$path';";
        $this->assertStringContainsString($expectedRequire, $contents);
        $this->assertStringContainsString('require_once $file', $contents);
    }

    public function test_excludes_phantom_preload_variable()
    {
        $this->list['$PRELOAD$'] = [
            'full_path' => '$PRELOAD$',
            'hits' => 0,
            'memory_consumption' => 560,
            'last_used' => 'Thu Jan  1 01:00:00 1970',
            'last_used_timestamp' => 0,
            'timestamp' => 0
        ];

        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->writeTo($this->preloaderPath);

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringNotContainsString('$PRELOAD$', $contents);
    }

    public function test_includes_error_when_files_dont_exists()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->writeTo($this->preloaderPath);

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringContainsString('throw new \Exception("{$file} does not exist or is unreadable.");', $contents);
        $this->assertStringNotContainsString('if (!(is_file($file) && is_readable($file))) {
            continue;
        }', $contents);
    }

    public function test_excludes_exception_if_file_doesnt_exists_by_default()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->ignoreNotFound()->writeTo($this->preloaderPath);

        $contents = file_get_contents($this->preloaderPath);

        $this->assertStringNotContainsString('throw new \Exception("{$file} does not exist or is unreadable.");', $contents);
        $this->assertStringContainsString('if (!(is_file($file) && is_readable($file))) {
            continue;
        }', $contents);
    }

    public function test_exception_when_autoload_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot proceed without a Composer Autoload.');

        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->useRequire(
            implode(DIRECTORY_SEPARATOR, [$this->workdir, 'doesntExists.php'])
        )->writeTo($this->preloaderPath);
    }

    public function test_raw_list()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $list = $preloader->getList();

        $this->assertIsArray($list);
        $this->assertCount(5, $list);
    }

    public function test_exception_when_opcache_disabled()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Opcache is disabled. No preload script can be generated.');

        $this->opcache->method('isEnabled')
            ->willReturn(false);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->writeTo('lol');
    }

    public function test_exception_when_no_scripts_cached()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Opcache reports 0 cached scripts. No preload can be generated.');

        $this->opcache->method('isEnabled')
            ->willReturn(true);
        $this->opcache->method('getNumberCachedScripts')
            ->willReturn(0);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $preloader->writeTo('lol');
    }

    public function test_exception_when_no_overwriting()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Preloader script already exists in the given path.');

        $this->opcache->method('isEnabled')
            ->willReturn(true);
        $this->opcache->method('getNumberCachedScripts')
            ->willReturn(1000);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        touch($this->preloaderPath);

        $this->assertFalse($preloader->writeTo($this->preloaderPath, false));
    }

    public function test_helper_creates_instance()
    {
        $this->assertInstanceOf(Preloader::class, Preloader::make());
    }

    public function test_accepts_empty_array_for_excluding()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $list = $preloader->exclude([])->getList();

        $this->assertIsArray($list);
        $this->assertCount(5, $list);
    }

    public function test_accepts_empty_array_for_appending()
    {
        $this->mockOpcache();

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister, $this->opcache);

        $list = $preloader->append([])->getList();

        $this->assertIsArray($list);
        $this->assertCount(5, $list);
    }

    protected function tearDown() : void
    {
        parent::tearDown();

        $this->clearWorkdir();
    }
}
