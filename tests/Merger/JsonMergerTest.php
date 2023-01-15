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

        $fileExists = $this->getFunctionMock('Williarin\Cook\Merger', 'file_exists');
        $fileExists->expects($this->atLeastOnce())
            ->willReturn(false);

        $filePutContents = $this->getFunctionMock('Williarin\Cook\Merger', 'file_put_contents');
        $filePutContents->expects($this->once())
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
            'destination' => 'tests/Dummy/composer.json',
            'entries' => [
                'scripts' => [
                    'post-create-project-cmd' => "php -r \"copy('config/local-example.php', 'config/local.php');\"",
                ],
                'new-entry' => 'some-config',
            ],
        ];

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./tests/Dummy', 0755);

        $fileExists = $this->getFunctionMock('Williarin\Cook\Merger', 'file_exists');
        $fileExists->expects($this->exactly(2))
            ->willReturn(true);

        $filePutContents = $this->getFunctionMock('Williarin\Cook\Merger', 'file_put_contents');
        $filePutContents->expects($this->once())
            ->with(
                './tests/Dummy/composer.json',
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
            ->with('Updated file: ./tests/Dummy/composer.json');

        $this->merger->merge($file);
    }
}
