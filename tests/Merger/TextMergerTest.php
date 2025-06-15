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
        $this->io->expects($this->once())
            ->method('write')
            ->with(
                '<error>Error found in williarin/cook-example recipe: "source" or "content" field is required for "text" file type.</>'
            );

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

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./var/cache/tests', 0755);

        $this->filesystem->expects($this->exactly(2))
            ->method('exists')
            ->willReturn(false);

        $expectedContent = <<<CODE_SAMPLE
            ###> williarin/cook-example ###
            SOME_ENV_VARIABLE='hello'
            ANOTHER_ENV_VARIABLE='world'
            ###< williarin/cook-example ###

            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./var/cache/tests/.env', $this->equalTo($expectedContent));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Created file: ./var/cache/tests/.env');

        $this->merger->merge($file);
        $this->addToAssertionCount(1);
    }

    public function testMergeNewFileWithContentAsFile(): void
    {
        $file = [
            'destination' => 'var/cache/tests/.env',
            'source' => '.env',
        ];

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./var/cache/tests', 0755);

        $this->filesystem->expects($this->exactly(3))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(true, false, false);

        $expectedContent = <<<CODE_SAMPLE
            ###> williarin/cook-example ###
            SOME_ENV_VARIABLE='hello'
            ANOTHER_ENV_VARIABLE='world'
            ###< williarin/cook-example ###

            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./var/cache/tests/.env', $this->equalTo($expectedContent));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Created file: ./var/cache/tests/.env');

        $this->merger->merge($file);
        $this->addToAssertionCount(1);
    }

    public function testMergeExistingFile(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/.env',
            'source' => '.env',
        ];

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->expects($this->exactly(3))
            ->method('exists')
            ->willReturn(true);

        $expectedContent = <<<CODE_SAMPLE
            APP_ENV=dev
            APP_SECRET=10a4bee52442dcf74a9f6b5a9afd319a
            DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=14&charset=utf8"

            ###> williarin/cook-example ###
            SOME_ENV_VARIABLE='hello'
            ANOTHER_ENV_VARIABLE='world'
            ###< williarin/cook-example ###

            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./tests/Dummy/before/.env', $this->equalTo($expectedContent));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/before/.env');

        $this->merger->merge($file);
        $this->addToAssertionCount(1);
    }

    public function testUninstallRecipe(): void
    {
        $file = [
            'destination' => 'tests/Dummy/after/.env',
            'source' => '.env',
        ];

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with('./tests/Dummy/after/.env')
            ->willReturn(true);

        $expectedContent = <<<CODE_SAMPLE
            APP_ENV=dev
            APP_SECRET=10a4bee52442dcf74a9f6b5a9afd319a
            DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=14&charset=utf8"


            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./tests/Dummy/after/.env', $this->equalTo($expectedContent));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/after/.env');

        $this->merger->uninstall($file);
        $this->addToAssertionCount(1);
    }
}
