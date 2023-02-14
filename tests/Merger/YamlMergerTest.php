<?php

declare(strict_types=1);

namespace Williarin\Cook\Test\Merger;

use Williarin\Cook\Merger\YamlMerger;

class YamlMergerTest extends MergerTestCase
{
    private YamlMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merger = new YamlMerger($this->io, $this->state, $this->filesystem, $this->filters);
    }

    public function testGetName(): void
    {
        $this->assertSame('yaml', $this->merger::getName());
    }

    public function testMergeWithoutContent(): void
    {
        $this->io->shouldReceive('write')
            ->once()
            ->with(
                '<error>Error found in williarin/cook-example recipe: "source" or "content" field is required for "yaml" file type.</>'
            )
        ;

        $this->merger->merge([]);
    }

    public function testMergeNewFileWithContentAsString(): void
    {
        $file = [
            'destination' => 'var/cache/tests/services.yaml',
            'content' => <<<CODE_SAMPLE
                parameters:
                    locale: fr
                
                services:
                    Some\Service: ~
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
                './var/cache/tests/services.yaml',
                <<<CODE_SAMPLE
                parameters:
                ###> williarin/cook-example ###
                    locale: fr
                ###< williarin/cook-example ###
                
                services:
                ###> williarin/cook-example ###
                    Some\Service: ~
                ###< williarin/cook-example ###
                
                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Created file: ./var/cache/tests/services.yaml');

        $this->merger->merge($file);
    }

    public function testMergeNewFileWithContentAsFile(): void
    {
        $file = [
            'destination' => 'var/cache/tests/services.yaml',
            'source' => 'services.yaml',
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
                './var/cache/tests/services.yaml',
                <<<CODE_SAMPLE
                parameters:
                ###> williarin/cook-example ###
                    locale: fr
                ###< williarin/cook-example ###
                
                services:
                ###> williarin/cook-example ###
                    Some\Service: ~
                ###< williarin/cook-example ###
                
                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Created file: ./var/cache/tests/services.yaml');

        $this->merger->merge($file);
    }

    public function testMergeExistingFile(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/services.yaml',
            'source' => 'services.yaml',
        ];

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->shouldReceive('exists')
            ->atLeast()
            ->once()
            ->andReturn(true);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './tests/Dummy/before/services.yaml',
                <<<CODE_SAMPLE
                parameters:
                ###> williarin/cook-example ###
                    locale: fr
                ###< williarin/cook-example ###
                    some_parameter: true
                    another_parameter: Hello world
                
                services:
                ###> williarin/cook-example ###
                    Some\Service: ~
                ###< williarin/cook-example ###
                    _defaults:
                        autowire: true
                        autoconfigure: true
                
                    App\:
                        resource: '../src/'
                        exclude:
                            - '../src/DependencyInjection/'
                            - '../src/Entity/'
                            - '../src/Kernel.php'

                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Updated file: ./tests/Dummy/before/services.yaml');

        $this->merger->merge($file);
    }

    public function testMergeWithBlankLines(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/services.yaml',
            'source' => 'services.yaml',
            'blank_line_after' => ['services'],
        ];

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->shouldReceive('exists')
            ->atLeast()
            ->once()
            ->andReturn(true);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './tests/Dummy/before/services.yaml',
                <<<CODE_SAMPLE
                parameters:
                ###> williarin/cook-example ###
                    locale: fr
                ###< williarin/cook-example ###
                    some_parameter: true
                    another_parameter: Hello world
                
                services:
                ###> williarin/cook-example ###
                    Some\Service: ~
                ###< williarin/cook-example ###
                
                    _defaults:
                        autowire: true
                        autoconfigure: true
                
                    App\:
                        resource: '../src/'
                        exclude:
                            - '../src/DependencyInjection/'
                            - '../src/Entity/'
                            - '../src/Kernel.php'

                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Updated file: ./tests/Dummy/before/services.yaml');

        $this->merger->merge($file);
    }

    public function testMergeValidSectionsOnly(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/services.yaml',
            'source' => 'services.yaml',
            'valid_sections' => ['services'],
        ];

        $this->filesystem->shouldReceive('mkdir')
            ->once()
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->shouldReceive('exists')
            ->atLeast()
            ->once()
            ->andReturn(true);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './tests/Dummy/before/services.yaml',
                <<<CODE_SAMPLE
                parameters:
                    some_parameter: true
                    another_parameter: Hello world
                
                services:
                ###> williarin/cook-example ###
                    Some\Service: ~
                ###< williarin/cook-example ###
                    _defaults:
                        autowire: true
                        autoconfigure: true
                
                    App\:
                        resource: '../src/'
                        exclude:
                            - '../src/DependencyInjection/'
                            - '../src/Entity/'
                            - '../src/Kernel.php'

                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Updated file: ./tests/Dummy/before/services.yaml');

        $this->merger->merge($file);
    }

    public function testUninstallRecipe(): void
    {
        $file = [
            'destination' => 'tests/Dummy/after/services.yaml',
            'source' => 'services.yaml',
            'blank_line_after' => ['services'],
        ];

        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $this->filesystem->shouldReceive('dumpFile')
            ->once()
            ->with(
                './tests/Dummy/after/services.yaml',
                <<<CODE_SAMPLE
                parameters:
                    some_parameter: true
                    another_parameter: Hello world
                
                services:
                    _defaults:
                        autowire: true
                        autoconfigure: true
                
                    App\:
                        resource: '../src/'
                        exclude:
                            - '../src/DependencyInjection/'
                            - '../src/Entity/'
                            - '../src/Kernel.php'
                
                CODE_SAMPLE
                ,
            );

        $this->io->shouldReceive('write')
            ->once()
            ->with('Updated file: ./tests/Dummy/after/services.yaml');

        $this->merger->uninstall($file);
    }
}
