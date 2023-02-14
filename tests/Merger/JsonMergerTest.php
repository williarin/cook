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
        $this->io->shouldReceive('write')
            ->once()
            ->with(
                '<error>Error found in williarin/cook-example recipe: file of type "json" requires "entries" field.</>'
            )
        ;

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

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./var/cache/tests', 0755);

        $this->filesystem->shouldReceive('exists')
            ->atLeast()
            ->once()
            ->andReturn(false);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './var/cache/tests/composer.json',
                <<<CODE_SAMPLE
                {
                    "scripts": {
                        "post-create-project-cmd": "php -r \"copy('config/local-example.php', 'config/local.php');\""
                    }
                }
                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
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

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->shouldReceive('exists')
            ->twice()
            ->andReturn(true);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './tests/Dummy/before/composer.json',
                <<<CODE_SAMPLE
                {
                    "name": "williarin/cook",
                    "scripts": {
                        "post-update-cmd": "MyVendor\\\\MyClass::postUpdate",
                        "post-package-install": [
                            "MyVendor\\\\MyClass::postPackageInstall"
                        ],
                        "post-create-project-cmd": "php -r \"copy('config/local-example.php', 'config/local.php');\""
                    },
                    "new-entry": "some-config"
                }
                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Updated file: ./tests/Dummy/before/composer.json');

        $this->merger->merge($file);
    }

    public function testUninstallWithoutEntries(): void
    {
        $this->io->shouldReceive('write')
            ->once()
            ->with(
                '<error>Error found in williarin/cook-example recipe: file of type "json" requires "entries" field.</>'
            )
        ;

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

        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './tests/Dummy/after/composer.json',
                <<<CODE_SAMPLE
                {
                    "name": "williarin/cook",
                    "scripts": {
                        "post-update-cmd": "MyVendor\\\\MyClass::postUpdate",
                        "post-package-install": [
                            "MyVendor\\\\MyClass::postPackageInstall"
                        ]
                    }
                }
                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Updated file: ./tests/Dummy/after/composer.json');

        $this->merger->uninstall($file);
    }
}
