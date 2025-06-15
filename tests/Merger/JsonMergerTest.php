<?php

declare(strict_types=1);

namespace Williarin\Cook\Test\Merger;

use Williarin\Cook\Merger\JsonMerger;

class JsonMergerTest extends MergerTestCase
{
    private JsonMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merger = new JsonMerger($this->io, $this->state, $this->filesystem, $this->filters);
    }

    public function testGetName(): void
    {
        $this->assertSame('json', $this->merger::getName());
    }

    public function testMergeWithoutEntries(): void
    {
        $this->io->expects($this->once())
            ->method('write')
            ->with(
                '<error>Error found in williarin/cook-example recipe: file of type "json" requires "entries" field.</>'
            );

        $this->merger->merge([]);
    }

    public function testMergeNewFile(): void
    {
        $file = [
            'destination' => 'var/cache/tests/composer.json',
            'entries' => [
                'scripts' => [
                    'post-create-project-cmd' => "php -r \"copy('config/local-example.php', 'config/local.php');\"",
                ],
            ],
        ];

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./var/cache/tests', 0755);

        $this->filesystem->expects($this->exactly(2))
            ->method('exists')
            ->willReturn(false);

        $expectedJson = json_encode([
            'scripts' => [
                'post-create-project-cmd' => "php -r \"copy('config/local-example.php', 'config/local.php');\"",
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./var/cache/tests/composer.json', $expectedJson);

        $this->io->expects($this->once())
            ->method('write')
            ->with('Created file: ./var/cache/tests/composer.json');

        $this->merger->merge($file);
    }

    public function testMergeExistingFile(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/composer.json',
            'entries' => [
                'scripts' => [
                    'post-create-project-cmd' => "php -r \"copy('config/local-example.php', 'config/local.php');\"",
                ],
                'new-entry' => 'some-config',
            ],
        ];

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->expects($this->exactly(2))
            ->method('exists')
            ->willReturn(true);

        $expectedData = [
            'name' => 'williarin/cook',
            'scripts' => [
                'post-update-cmd' => 'MyVendor\\MyClass::postUpdate',
                'post-package-install' => ['MyVendor\\MyClass::postPackageInstall'],
                'post-create-project-cmd' => "php -r \"copy('config/local-example.php', 'config/local.php');\"",
            ],
            'new-entry' => 'some-config',
        ];

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with(
                './tests/Dummy/before/composer.json',
                json_encode($expectedData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/before/composer.json');

        $this->merger->merge($file);
    }

    public function testUninstallWithoutEntries(): void
    {
        $this->io->expects($this->once())
            ->method('write')
            ->with(
                '<error>Error found in williarin/cook-example recipe: file of type "json" requires "entries" field.</>'
            );

        $this->merger->uninstall([]);
    }

    public function testUninstallRecipe(): void
    {
        $file = [
            'destination' => 'tests/Dummy/after/composer.json',
            'entries' => [
                'scripts' => [
                    'post-create-project-cmd' => "php -r \"copy('config/local-example.php', 'config/local.php');\"",
                ],
                'new-entry' => 'some-config',
            ],
        ];

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $expectedData = [
            'name' => 'williarin/cook',
            'scripts' => [
                'post-update-cmd' => 'MyVendor\\MyClass::postUpdate',
                'post-package-install' => ['MyVendor\\MyClass::postPackageInstall'],
            ],
        ];

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with(
                './tests/Dummy/after/composer.json',
                json_encode($expectedData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/after/composer.json');

        $this->merger->uninstall($file);
    }
}
