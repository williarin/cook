<?php

declare(strict_types=1);

namespace Williarin\Cook\Test;

use Composer\Composer;
use Composer\Package\RootPackageInterface;
use PHPUnit\Framework\TestCase;
use Williarin\Cook\Options;

class OptionsTest extends TestCase
{
    public function testInitOptionsDefault(): void
    {
        $package = $this->createMock(RootPackageInterface::class);
        $package->method('getExtra')
            ->willReturn([]);

        $composer = $this->createMock(Composer::class);
        $composer->method('getPackage')
            ->willReturn($package);

        $options = new Options($composer);

        $this->assertSame([
            'bin-dir' => 'bin',
            'config-dir' => 'config',
            'src-dir' => 'src',
            'var-dir' => 'var',
            'public-dir' => 'public',
            'root-dir' => '.',
        ], $options->all());
    }

    public function testInitOptionsWithOverriddenVariable(): void
    {
        $extra = [
            'bin-dir' => 'custom/bin',
            'config-dir' => 'custom/config',
            'src-dir' => 'custom/src',
            'var-dir' => 'custom/var',
            'public-dir' => 'custom/public',
            'root-dir' => 'custom/',
        ];

        $package = $this->createMock(RootPackageInterface::class);
        $package->method('getExtra')
            ->willReturn($extra);

        $composer = $this->createMock(Composer::class);
        $composer->method('getPackage')
            ->willReturn($package);

        $options = new Options($composer);

        $this->assertSame($extra, $options->all());
    }

    public function testGetOption(): void
    {
        $package = $this->createMock(RootPackageInterface::class);
        $package->method('getExtra')
            ->willReturn([]);

        $composer = $this->createMock(Composer::class);
        $composer->method('getPackage')
            ->willReturn($package);

        $options = new Options($composer);

        $this->assertSame('bin', $options->get('bin-dir'));
        $this->assertNull($options->get('non-existent-option'));
    }
}
