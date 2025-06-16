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
        $this->io->expects($this->once())
            ->method('write')
            ->with(
                '<error>Error found in williarin/cook-example recipe: "source" or "content" field is required for "yaml" file type.</>'
            );

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

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./var/cache/tests', 0755);

        $this->filesystem->expects($this->exactly(2))
            ->method('exists')
            ->willReturn(false);

        $expectedContent = <<<CODE_SAMPLE
            parameters:
            ###> williarin/cook-example ###
                locale: fr
            ###< williarin/cook-example ###
            
            services:
            ###> williarin/cook-example ###
                Some\Service: ~
            ###< williarin/cook-example ###
            
            CODE_SAMPLE;


        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./var/cache/tests/services.yaml', $this->equalTo(trim($expectedContent) . "\n"));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Created file: ./var/cache/tests/services.yaml');

        $this->merger->merge($file);
        $this->addToAssertionCount(1);
    }

    public function testMergeNewFileWithContentAsFile(): void
    {
        $file = [
            'destination' => 'var/cache/tests/services.yaml',
            'source' => 'services.yaml',
        ];

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./var/cache/tests', 0755);

        $this->filesystem->expects($this->exactly(3))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(true, false, false);

        $expectedContent = <<<CODE_SAMPLE
            parameters:
            ###> williarin/cook-example ###
                locale: fr
            ###< williarin/cook-example ###
            
            services:
            ###> williarin/cook-example ###
                Some\Service: ~
            ###< williarin/cook-example ###
            
            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./var/cache/tests/services.yaml', $this->equalTo(trim($expectedContent) . "\n"));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Created file: ./var/cache/tests/services.yaml');

        $this->merger->merge($file);
        $this->addToAssertionCount(1);
    }

    public function testMergeExistingFile(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/services.yaml',
            'source' => 'services.yaml',
        ];

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->expects($this->atLeastOnce())
            ->method('exists')
            ->willReturn(true);

        $expectedContent = <<<CODE_SAMPLE
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
            
            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./tests/Dummy/before/services.yaml', $this->equalTo(trim($expectedContent) . "\n"));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/before/services.yaml');

        $this->merger->merge($file);
        $this->addToAssertionCount(1);
    }

    public function testMergeWithBlankLines(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/services.yaml',
            'source' => 'services.yaml',
            'blank_line_after' => ['services'],
        ];

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->expects($this->atLeastOnce())
            ->method('exists')
            ->willReturn(true);

        $expectedContent = <<<CODE_SAMPLE
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

            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./tests/Dummy/before/services.yaml', $this->equalTo(trim($expectedContent) . "\n"));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/before/services.yaml');

        $this->merger->merge($file);
        $this->addToAssertionCount(1);
    }

    public function testMergeValidSectionsOnly(): void
    {
        $file = [
            'destination' => 'tests/Dummy/before/services.yaml',
            'source' => 'services.yaml',
            'valid_sections' => ['services'],
        ];

        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with('./tests/Dummy/before', 0755);

        $this->filesystem->expects($this->atLeastOnce())
            ->method('exists')
            ->willReturn(true);

        $expectedContent = <<<CODE_SAMPLE
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
            
            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./tests/Dummy/before/services.yaml', $this->equalTo(trim($expectedContent) . "\n"));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/before/services.yaml');

        $this->merger->merge($file);
        $this->addToAssertionCount(1);
    }

    public function testUninstallRecipe(): void
    {
        $file = [
            'destination' => 'tests/Dummy/after/services.yaml',
            'source' => 'services.yaml',
            'blank_line_after' => ['services'],
        ];

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $expectedContent = <<<CODE_SAMPLE
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
            
            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./tests/Dummy/after/services.yaml', $this->equalTo(trim($expectedContent) . "\n"));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/after/services.yaml');

        $this->merger->uninstall($file);
        $this->addToAssertionCount(1);
    }

    public function testUninstallRecipeUninstallEmptySection(): void
    {
        $file = [
            'destination' => 'tests/Dummy/after/routes.yaml',
            'source' => 'routes.yaml',
            'uninstall_empty_sections' => true,
        ];

        $this->filesystem->expects($this->atLeastOnce())
            ->method('exists')
            ->willReturn(true);

        $expectedContent = <<<CODE_SAMPLE
            controllers:
                resource:
                    path: ../src/Controller/
                    namespace: App\Controller
                type: attribute
            
            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./tests/Dummy/after/routes.yaml', $this->equalTo(trim($expectedContent) . "\n"));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/after/routes.yaml');

        $this->merger->uninstall($file);
        $this->addToAssertionCount(1);
    }

    public function testUninstallRecipeWithoutUninstallEmptySection(): void
    {
        $file = [
            'destination' => 'tests/Dummy/after/routes.yaml',
            'source' => 'routes.yaml',
        ];

        $this->filesystem->expects($this->atLeastOnce())
            ->method('exists')
            ->willReturn(true);

        $expectedContent = <<<CODE_SAMPLE
            controllers:
                resource:
                    path: ../src/Controller/
                    namespace: App\Controller
                type: attribute
            
            other_routes:
            
            CODE_SAMPLE;

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('./tests/Dummy/after/routes.yaml', $this->equalTo(trim($expectedContent) . "\n"));

        $this->io->expects($this->once())
            ->method('write')
            ->with('Updated file: ./tests/Dummy/after/routes.yaml');

        $this->merger->uninstall($file);
        $this->addToAssertionCount(1);
    }
}
