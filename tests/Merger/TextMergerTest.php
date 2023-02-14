<?php

declare(strict_types=1);

namespace Williarin\Cook\Test\Merger;

use Williarin\Cook\Merger\TextMerger;

class TextMergerTest extends MergerTestCase
{
    private TextMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merger = new TextMerger($this->io, $this->state, $this->filesystem, $this->filters);
    }

    public function testGetName(): void
    {
        $this->assertSame('text', $this->merger::getName());
    }

    public function testMergeWithoutContent(): void
    {
        $this->io->shouldReceive('write')
            ->once()
            ->with(
                '<error>Error found in williarin/cook-example recipe: "source" or "content" field is required for "text" file type.</>'
            )
        ;

        $this->merger->merge([]);
    }

    public function testMergeNewFileWithContentAsString(): void
    {
        $file = [
            'destination' => 'var/cache/tests/.env',
            'content' => <<<CODE_SAMPLE
                SOME_ENV_VARIABLE='hello'
                ANOTHER_ENV_VARIABLE='world'
                CODE_SAMPLE
            ,
        ];

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./var/cache/tests', 0755);

        $this->filesystem->shouldReceive('exists')
            ->twice()
            ->andReturn(false);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './var/cache/tests/.env',
                <<<CODE_SAMPLE
                ###> williarin/cook-example ###
                SOME_ENV_VARIABLE='hello'
                ANOTHER_ENV_VARIABLE='world'
                ###< williarin/cook-example ###
                
                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Created file: ./var/cache/tests/.env');

        $this->merger->merge($file);
    }

    public function testMergeNewFileWithContentAsFile(): void
    {
        $file = [
            'destination' => 'var/cache/tests/.env',
            'source' => '.env',
        ];

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./var/cache/tests', 0755);

        $this->filesystem->shouldReceive('exists')
            ->times(3)
            ->andReturn(true, false, false);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './var/cache/tests/.env',
                <<<CODE_SAMPLE
                ###> williarin/cook-example ###
                SOME_ENV_VARIABLE='hello'
                ANOTHER_ENV_VARIABLE='world'
                ###< williarin/cook-example ###
                
                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Created file: ./var/cache/tests/.env');

        $this->merger->merge($file);
    }

    public function testMergeExistingFile(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/.env',
            'source' => '.env',
        ];

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->shouldReceive('exists')
            ->times(3)
            ->andReturn(true);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './tests/Dummy/before/.env',
                <<<CODE_SAMPLE
                APP_ENV=dev
                APP_SECRET=10a4bee52442dcf74a9f6b5a9afd319a
                DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=14&charset=utf8"

                ###> williarin/cook-example ###
                SOME_ENV_VARIABLE='hello'
                ANOTHER_ENV_VARIABLE='world'
                ###< williarin/cook-example ###
                
                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Updated file: ./tests/Dummy/before/.env');

        $this->merger->merge($file);
    }

    public function testUninstallRecipe(): void
    {
        $file = [
            'destination' => 'tests/Dummy/after/.env',
            'source' => '.env',
        ];

        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './tests/Dummy/after/.env',
                <<<CODE_SAMPLE
                APP_ENV=dev
                APP_SECRET=10a4bee52442dcf74a9f6b5a9afd319a
                DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=14&charset=utf8"
                \n
                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Updated file: ./tests/Dummy/after/.env');

        $this->merger->uninstall($file);
    }
}
