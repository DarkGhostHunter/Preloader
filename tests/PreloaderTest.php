<?php

namespace Tests;

use DarkGhostHunter\Preloader\Opcache;
use DarkGhostHunter\Preloader\Preloader;
use PHPUnit\Framework\TestCase;
use DarkGhostHunter\Preloader\PreloaderLister;
use DarkGhostHunter\Preloader\PreloaderCompiler;

class PreloaderTest extends TestCase
{
    protected string $workdir;

    protected array $list = [
        'foo' => [ // 3
            'hits' => 10,
            'memory_consumption' => 1 * (1024 ^ 2),
            'last_used_timestamp' => 1400000000
        ],
        'bar' => [ // 1
            'hits' => 20,
            'memory_consumption' => 3 * (1024 ^ 2),
            'last_used_timestamp' => 1400000002
        ],
        'quz' => [ // 2
            'hits' => 20,
            'memory_consumption' => 5 * (1024 ^ 2),
            'last_used_timestamp' => 1400000001
        ],
        'qux' => [ // 4
            'hits' => 5,
            'memory_consumption' => 5 * (1024 ^ 2),
            'last_used_timestamp' => 1400000010
        ],
        'baz' => [ // 5
            'hits' => 5,
            'memory_consumption' => 6 * (1024 ^ 2),
            'last_used_timestamp' => 1400000010
        ]
    ];

    protected function setUp() : void
    {
        parent::setUp();

        $this->workdir = realpath(__DIR__ . '/workdir');

        if (is_file($file = $this->workdir . '/preload.php')) {
            unlink($file);
        }
    }

    public function testCreatesInstance()
    {
        $preloader = Preloader::make();

        $this->assertInstanceOf(Preloader::class, $preloader);

        $fromStatic = Preloader::make()->output($this->workdir . '/preload.php')
            ->autoload($this->workdir . '/autoload.php');

        $this->assertInstanceOf(Preloader::class, $fromStatic);
    }

    public function testGeneratesScript()
    {
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(1000);
        $opcache->method('getHits')
            ->willReturn(1001);
        $opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => $usedMemory = rand(1000, 999999),
                    'free_memory' => $freeMemory = rand(1000, 999999),
                    'wasted_memory' => $wastedMemory = rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => $cachedScripts = rand(1000, 999999),
                    'opcache_hit_rate' => $hitRate = rand(1, 99)/100,
                    'misses' => $misses = rand(1000, 999999),
                ],
            ]);
        $opcache->method('getScripts')
            ->willReturn($this->list);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $preloader
            ->autoload($autoload = $this->workdir . '/autoload.php')
            ->output($output = $this->workdir . '/preload.php');

        $this->assertTrue($preloader->generate());
        $this->assertFileExists($this->workdir . '/preload.php');

        $contents = file_get_contents($this->workdir . '/preload.php');

        $files = '$files = [' . \PHP_EOL .
            "    'bar'," . \PHP_EOL .
            "    'quz'," . \PHP_EOL .
            "    'foo'," . \PHP_EOL .
            "    'qux'," . \PHP_EOL .
            "    'baz'" . \PHP_EOL .
            '];';

        $this->assertStringContainsString($files, $contents);
        $this->assertStringContainsString('opcache.preload=' . $output, $contents);

        $this->assertRegExp('/([0-9]+)-(0{0,1}[1-9]|10|11|12)-([0-2]{0,1}[1-9]|10|20|30|31)\s([01]{0,1}[0-9]|2[0-3]):([0-5]{0,1}[0-9]):([0-5]{0,1}[0-9])/', $contents);

        $this->assertStringContainsString('Used Memory: ' . $usedMemory, $contents);
        $this->assertStringContainsString('Free Memory: ' . $freeMemory, $contents);
        $this->assertStringContainsString('Wasted Memory: ' . $wastedMemory, $contents);
        $this->assertStringContainsString('Cached files: ' . $cachedScripts, $contents);
        $this->assertStringContainsString('Hit rate: ' . $hitRate * 100 . '%', $contents);

        $this->assertStringContainsString('Misses: ' . $misses, $contents);
        $this->assertStringContainsString('Memory limit: 32 MB', $contents);
        $this->assertStringContainsString('Overwrite: false', $contents);
        $this->assertStringContainsString('Files excluded: 0', $contents);
        $this->assertStringContainsString('Files appended: 0', $contents);

        $this->assertStringContainsString("require_once '{$autoload}';", $contents);
    }

    public function testMemoryLimit()
    {
        $opcache = $this->createMock(Opcache::class);

        $list = [
            'foo' => [ // 3
                'hits' => 10,
                'memory_consumption' => 14 * (1024 ^ 2),
                'last_used_timestamp' => 1400000000
            ],
            'bar' => [ // 1
                'hits' => 20,
                'memory_consumption' => 13 * (1024 ^ 2),
                'last_used_timestamp' => 1400000002
            ],
            'quz' => [ // 2
                'hits' => 20,
                'memory_consumption' => 12 * (1024 ^ 2),
                'last_used_timestamp' => 1400000001
            ],
            'qux' => [ // 4
                'hits' => 5,
                'memory_consumption' => 11 * (1024 ^ 2),
                'last_used_timestamp' => 1400000010
            ],
            'baz' => [ // 5
                'hits' => 5,
                'memory_consumption' => 10 * (1024 ^ 2),
                'last_used_timestamp' => 1400000010
            ]
        ];

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(count($list));
        $opcache->method('getHits')
            ->willReturn(1001);
        $opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => rand(1000, 999999),
                    'free_memory' => rand(1000, 999999),
                    'wasted_memory' => rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => rand(1000, 999999),
                    'opcache_hit_rate' => rand(1, 99)/100,
                    'misses' => rand(1000, 999999),
                ],
            ]);
        $opcache->method('getScripts')
            ->willReturn($list);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $preloader
            ->autoload($autoload = $this->workdir . '/autoload.php')
            ->memory(0)
            ->output($this->workdir . '/preload.php');

        $this->assertTrue($preloader->generate());
        $this->assertFileExists($this->workdir . '/preload.php');

        $contents = file_get_contents($this->workdir . '/preload.php');

        $files = '$files = [' . \PHP_EOL .
            "    'bar'," . \PHP_EOL .
            "    'quz'," . \PHP_EOL .
            "    'foo'," . \PHP_EOL .
            "    'baz'," . \PHP_EOL .
            "    'qux'" . \PHP_EOL .
            '];';

        $this->assertStringContainsString($files, $contents);
        $this->assertStringContainsString('Memory limit: 0 MB', $contents);
    }

    public function testNoMemoryLimit()
    {
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(count($this->list));
        $opcache->method('getHits')
            ->willReturn(1001);
        $opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => rand(1000, 999999),
                    'free_memory' => rand(1000, 999999),
                    'wasted_memory' => rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => rand(1000, 999999),
                    'opcache_hit_rate' => rand(1, 99)/100,
                    'misses' => rand(1000, 999999),
                ],
            ]);
        $opcache->method('getScripts')
            ->willReturn($this->list);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $preloader
            ->autoload($autoload = $this->workdir . '/autoload.php')
            ->memory(10)
            ->output($this->workdir . '/preload.php');

        $this->assertTrue($preloader->generate());
        $this->assertFileExists($this->workdir . '/preload.php');

        $contents = file_get_contents($this->workdir . '/preload.php');

        $files = '$files = [' . \PHP_EOL .
            "    'bar'," . \PHP_EOL .
            "    'quz'," . \PHP_EOL .
            "    'foo'" . \PHP_EOL .
            '];';

        $this->assertStringContainsString($files, $contents);
        $this->assertStringContainsString('Memory limit: 10 MB', $contents);
    }

    public function testWhenHits()
    {
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(1000);
        $opcache->method('getHits')
            ->willReturn(1001);
        $opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => rand(1000, 999999),
                    'free_memory' => rand(1000, 999999),
                    'wasted_memory' => rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => rand(1000, 999999),
                    'opcache_hit_rate' => rand(1, 99)/100,
                    'misses' => rand(1000, 999999),
                ],
            ]);
        $opcache->method('getScripts')
            ->willReturn($this->list);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $this->assertFalse(
            $preloader->whenHits(1000)
                ->autoload($this->workdir . '/autoload.php')
                ->output($this->workdir . '/preload.php')
                ->generate()
        );

        $this->assertFalse(
            $preloader->whenHits(1001)
                ->autoload($this->workdir . '/autoload.php')
                ->output($this->workdir . '/preload.php')
                ->generate()
        );

        $this->assertTrue(
            $preloader->whenHits(1002)
                ->autoload($this->workdir . '/autoload.php')
                ->output($this->workdir . '/preload.php')
                ->generate()
        );
    }

    public function testWhen()
    {
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(1000);
        $opcache->method('getHits')
            ->willReturn(1001);
        $opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => rand(1000, 999999),
                    'free_memory' => rand(1000, 999999),
                    'wasted_memory' => rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => rand(1000, 999999),
                    'opcache_hit_rate' => rand(1, 99)/100,
                    'misses' => rand(1000, 999999),
                ],
            ]);
        $opcache->method('getScripts')
            ->willReturn($this->list);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $this->assertFalse(
            $preloader
                ->autoload($this->workdir . '/autoload.php')
                ->output($this->workdir. '/preload.php')
                ->when(fn () => false)
                ->generate()
        );

        $this->assertFalse(
            $preloader
                ->autoload($this->workdir . '/autoload.php')
                ->output($this->workdir. '/preload.php')
                ->when(fn () => null)
                ->generate()
        );

        $this->assertFalse(
            $preloader
                ->autoload($this->workdir . '/autoload.php')
                ->output($this->workdir. '/preload.php')
                ->when(function() { })
                ->generate()
        );

        $this->assertTrue(
            $preloader
                ->autoload($this->workdir . '/autoload.php')
                ->output($this->workdir. '/preload.php')
                ->when(fn () => true)
                ->generate()
        );
    }

    public function testAppendsFilesWithoutAffectingLimits()
    {
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(count($this->list));
        $opcache->method('getHits')
            ->willReturn(1001);
        $opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => rand(1000, 999999),
                    'free_memory' => rand(1000, 999999),
                    'wasted_memory' => rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => rand(1000, 999999),
                    'opcache_hit_rate' => rand(1, 99)/100,
                    'misses' => rand(1000, 999999),
                ],
            ]);
        $opcache->method('getScripts')
            ->willReturn($this->list);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $preloader
            ->autoload($autoload = $this->workdir . '/autoload.php')
            ->memory(10)
            ->append([
                'test_a',
                'test_b'
            ])
            ->output($this->workdir . '/preload.php');

        $this->assertTrue($preloader->generate());
        $this->assertFileExists($this->workdir . '/preload.php');

        $contents = file_get_contents($this->workdir . '/preload.php');

        $files = '$files = [' . \PHP_EOL .
            "    'bar'," . \PHP_EOL .
            "    'quz'," . \PHP_EOL .
            "    'foo'," . \PHP_EOL .
            "    'test_a'," . \PHP_EOL .
            "    'test_b'" . \PHP_EOL .
            '];';

        $this->assertStringContainsString($files, $contents);
        $this->assertStringContainsString('Memory limit: 10 MB', $contents);
        $this->assertStringContainsString('Files appended: 2', $contents);
    }

    public function testExcludesFilesAffectingLimits()
    {
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(count($this->list));
        $opcache->method('getHits')
            ->willReturn(1001);
        $opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => rand(1000, 999999),
                    'free_memory' => rand(1000, 999999),
                    'wasted_memory' => rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => rand(1000, 999999),
                    'opcache_hit_rate' => rand(1, 99)/100,
                    'misses' => rand(1000, 999999),
                ],
            ]);
        $opcache->method('getScripts')
            ->willReturn($this->list);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $preloader
            ->autoload($autoload = $this->workdir . '/autoload.php')
            ->memory(10)
            ->exclude([
                'quz',
            ])
            ->output($this->workdir . '/preload.php');

        $this->assertTrue($preloader->generate());
        $this->assertFileExists($this->workdir . '/preload.php');

        $contents = file_get_contents($this->workdir . '/preload.php');

        $files = '$files = [' . \PHP_EOL .
            "    'bar'," . \PHP_EOL .
            "    'foo'," . \PHP_EOL .
            "    'qux'" . \PHP_EOL .
            '];';

        $this->assertStringContainsString($files, $contents);
        $this->assertStringContainsString('Memory limit: 10 MB', $contents);
        $this->assertStringContainsString('Files excluded: 1', $contents);
    }

    public function testExcludesPackageFiles()
    {
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(count($this->list));
        $opcache->method('getHits')
            ->willReturn(1001);
        $opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => rand(1000, 999999),
                    'free_memory' => rand(1000, 999999),
                    'wasted_memory' => rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => rand(1000, 999999),
                    'opcache_hit_rate' => rand(1, 99)/100,
                    'misses' => rand(1000, 999999),
                ],
            ]);

        $package = array_flip([
            realpath(__DIR__ . '/../src/Conditions.php'),
            realpath(__DIR__ . '/../src/GeneratesScript.php'),
            realpath(__DIR__ . '/../src/LimitsList.php'),
            realpath(__DIR__ . '/../src/ManagesFiles.php'),
            realpath(__DIR__ . '/../src/Opcache.php'),
            realpath(__DIR__ . '/../src/Preloader.php'),
            realpath(__DIR__ . '/../src/PreloaderCompiler.php'),
            realpath(__DIR__ . '/../src/PreloaderLister.php'),
        ]);

        $opcache->method('getScripts')
            ->willReturn(array_merge($this->list, $package));

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $preloader
            ->autoload($autoload = $this->workdir . '/autoload.php')
            ->memory(10)
            ->exclude([
                'quz',
            ])
            ->output($this->workdir . '/preload.php')
            ->generate();

        $contents = file_get_contents($this->workdir . '/preload.php');

        $files = '$files = [' . \PHP_EOL .
            "    'bar'," . \PHP_EOL .
            "    'foo'," . \PHP_EOL .
            "    'qux'" . \PHP_EOL .
            '];';

        $this->assertStringContainsString($files, $contents);
    }

    public function testExcludesAndAppends()
    {
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(count($this->list));
        $opcache->method('getHits')
            ->willReturn(1001);
        $opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => rand(1000, 999999),
                    'free_memory' => rand(1000, 999999),
                    'wasted_memory' => rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => rand(1000, 999999),
                    'opcache_hit_rate' => rand(1, 99)/100,
                    'misses' => rand(1000, 999999),
                ],
            ]);
        $opcache->method('getScripts')
            ->willReturn($this->list);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $preloader
            ->autoload($autoload = $this->workdir . '/autoload.php')
            ->memory(10)
            ->exclude('quz')
            ->append('test_a')
            ->output($this->workdir . '/preload.php');

        $this->assertTrue($preloader->generate());
        $this->assertFileExists($this->workdir . '/preload.php');

        $contents = file_get_contents($this->workdir . '/preload.php');

        $files = '$files = [' . \PHP_EOL .
            "    'bar'," . \PHP_EOL .
            "    'foo'," . \PHP_EOL .
            "    'qux'," . \PHP_EOL .
            "    'test_a'" . \PHP_EOL .
            '];';

        $this->assertStringContainsString($files, $contents);
        $this->assertStringContainsString('Memory limit: 10 MB', $contents);
        $this->assertStringContainsString('Files excluded: 1', $contents);
        $this->assertStringContainsString('Files appended: 1', $contents);
    }

    public function testOverwritesPreloader()
    {
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(count($this->list));
        $opcache->method('getHits')
            ->willReturn(1001);
        $opcache->method('getStatus')
            ->willReturn([
                'memory_usage' => [
                    'used_memory' => rand(1000, 999999),
                    'free_memory' => rand(1000, 999999),
                    'wasted_memory' => rand(1000, 999999),
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => rand(1000, 999999),
                    'opcache_hit_rate' => rand(1, 99)/100,
                    'misses' => rand(1000, 999999),
                ],
            ]);
        $opcache->method('getScripts')
            ->willReturn($this->list);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        file_put_contents($this->workdir . '/preload.php', 'this_is_a_test');

        $this->assertFalse(
            $preloader
                ->autoload($autoload = $this->workdir . '/autoload.php')
                ->memory(10)
                ->output($this->workdir . '/preload.php')
                ->generate()
        );

        $this->assertTrue(
            $preloader
                ->autoload($autoload = $this->workdir . '/autoload.php')
                ->memory(10)
                ->overwrite()
                ->output($this->workdir . '/preload.php')
                ->generate()
        );

        $contents = file_get_contents($this->workdir . '/preload.php');

        $this->assertStringNotContainsString('this_is_a_test', $contents);
    }

    public function testFailsWhenNoAutoloader()
    {
        $this->expectException(\LogicException::class);
        $this->expectErrorMessage('Cannot proceed without pointing where to output the script.');

        Preloader::make()
            ->autoload($this->workdir . '/autoload.php')
            ->generate();
    }

    public function testFailsWhenNoAutoload()
    {
        $this->expectException(\LogicException::class);
        $this->expectErrorMessage('Cannot proceed without an Composer Autoload.');

        Preloader::make()
            ->output($this->workdir . '/preload.php')
            ->generate();
    }

    public function testFailsWhenAutoloadDoesntExists()
    {
        $this->expectException(\LogicException::class);
        $this->expectErrorMessage('Composer Autoload file doesn\'t exists.');

        Preloader::make()
            ->output($this->workdir . '/preload.php')
            ->autoload($this->workdir . '/invalid_autoload.php')
            ->generate();
    }

    public function testFailsWhenOpcacheDisabled()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Opcache is disabled. No preload script can be generated.');
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(false);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $preloader
            ->output($this->workdir . '/preload.php')
            ->autoload($this->workdir . '/autoload.php')
            ->generate();
    }

    public function testFailsWhenOpcacheNoCachedScripts()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Opcache reports 0 cached scripts. No preload will be generated.');
        $opcache = $this->createMock(Opcache::class);

        $opcache->method('isEnabled')
            ->willReturn(true);
        $opcache->method('getNumberCachedScripts')
            ->willReturn(0);

        $preloader = new Preloader(new PreloaderCompiler, new PreloaderLister($opcache), $opcache);

        $preloader
            ->output($this->workdir . '/preload.php')
            ->autoload($this->workdir . '/autoload.php')
            ->generate();
    }

    protected function tearDown() : void
    {
        parent::tearDown();

        if (is_file($file = $this->workdir . '/preload.php')) {
            unlink($file);
        }
    }
}
