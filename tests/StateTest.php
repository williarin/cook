<?php

declare(strict_types=1);

namespace Williarin\Cook\Test;

use Composer\Composer;
use Composer\Package\RootPackageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Williarin\Cook\Options;
use Williarin\Cook\State;

class StateTest extends TestCase
{
    private State $state;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $package = $this->createMock(RootPackageInterface::class);
        $package->method('getExtra')
            ->willReturn([]);

        $composer = $this->createMock(Composer::class);
        $composer->method('getPackage')
            ->willReturn($package);

        $options = new Options($composer);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->state = new State($this->filesystem, $options);
    }

    public function testGetCurrentPackage(): void
    {
        $this->assertNull($this->state->getCurrentPackage());
        $this->state->setCurrentPackage('williarin/cook-example');
        $this->assertSame('williarin/cook-example', $this->state->getCurrentPackage());
    }

    public function testGetOverwrite(): void
    {
        $this->state->setOverwrite(true);
        $this->assertTrue($this->state->getOverwrite());
    }

    public function testGetProjectDirectory(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');
        $this->assertSame('.', $this->state->getProjectDirectory());
    }

    public function testGetVendorDirectory(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');
        $this->assertSame('./vendor', $this->state->getVendorDirectory());
    }

    public function testGetCurrentPackageDirectory(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');
        $this->assertSame('./vendor/williarin/cook-example', $this->state->getCurrentPackageDirectory());
    }

    public function testGetCurrentPackageRecipePathnameYaml(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with('./vendor/williarin/cook-example/cook.yaml')
            ->willReturn(true);

        $this->assertSame('./vendor/williarin/cook-example/cook.yaml', $this->state->getCurrentPackageRecipePathname());
    }

    public function testGetCurrentPackageRecipePathnameJson(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');

        $this->filesystem->expects($this->exactly(2))
            ->method('exists')
            ->willReturnMap([
                ['./vendor/williarin/cook-example/cook.yaml', false],
                ['./vendor/williarin/cook-example/cook.json', true],
            ]);

        $this->assertSame('./vendor/williarin/cook-example/cook.json', $this->state->getCurrentPackageRecipePathname());
    }

    public function testGetCurrentPackageRecipePathnameNotFound(): void
    {
        $this->state->setCurrentPackage('williarin/cook-example');

        $this->filesystem->expects($this->exactly(2))
            ->method('exists')
            ->willReturn(false);

        $this->assertNull($this->state->getCurrentPackageRecipePathname());
    }

    public function testReplacePathPlaceholders(): void
    {
        $this->assertSame('config/bundles.php', $this->state->replacePathPlaceholders('%CONFIG_DIR%/bundles.php'));
    }

    public function testReplacePathPlaceholdersWithNonExistentPlaceholders(): void
    {
        $this->assertSame(
            '%UNDEFINED_PLACEHOLDER%/bundles.php',
            $this->state->replacePathPlaceholders('%UNDEFINED_PLACEHOLDER%/bundles.php')
        );
    }
}
