<?php

declare(strict_types=1);

namespace Williarin\Cook\Test\Merger;

use Composer\IO\IOInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Williarin\Cook\StateInterface;

abstract class MergerTestCase extends TestCase
{
    protected IOInterface|MockObject $io;
    protected Filesystem|MockObject $filesystem;
    protected ServiceLocator|MockObject $filters;
    protected StateInterface|MockObject $state;

    private array $filterMap = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->io = $this->createMock(IOInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->state = $this->createMock(StateInterface::class);
        $this->filters = $this->createMock(ServiceLocator::class);

        $this->filters->method('has')
            ->willReturnCallback(fn (string $name): bool => isset($this->filterMap[$name]));

        $this->filters->method('get')
            ->willReturnCallback(function (string $name) {
                if (isset($this->filterMap[$name])) {
                    $className = $this->filterMap[$name];
                    return new $className();
                }

                return null;
            });

        $this->state
            ->method('getCurrentPackage')
            ->willReturn('williarin/cook-example');
        $this->state
            ->method('getCurrentPackageDirectory')
            ->willReturn('tests/Dummy/recipe');
        $this->state
            ->method('getProjectDirectory')
            ->willReturn('.');
        $this->state
            ->method('replacePathPlaceholders')
            ->willReturnArgument(0);
    }

    protected function addFilter(string $name, string $className): void
    {
        $this->filterMap[$name] = $className;
    }
}
