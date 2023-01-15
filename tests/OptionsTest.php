<?php

declare(strict_types=1);

namespace Williarin\Cook\Test;

use Composer\Composer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Williarin\Cook\Options;

class OptionsTest extends MockeryTestCase
{
    public function testInitOptionsDefault(): void
    {
        $composer = Mockery::mock(Composer::class);
        $composer->shouldReceive('getPackage->getExtra');
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

    public function testInitOptionsWithOverridenVariable(): void
    {
        $composer = Mockery::mock(Composer::class);
        $composer->shouldReceive('getPackage->getExtra')
            ->andReturn([
                'bin-dir' => 'custom/bin',
                'config-dir' => 'custom/config',
                'src-dir' => 'custom/src',
                'var-dir' => 'custom/var',
                'public-dir' => 'custom/public',
                'root-dir' => 'custom/',
            ]);
        $options = new Options($composer);

        $this->assertSame([
            'bin-dir' => 'custom/bin',
            'config-dir' => 'custom/config',
            'src-dir' => 'custom/src',
            'var-dir' => 'custom/var',
            'public-dir' => 'custom/public',
            'root-dir' => 'custom/',
        ], $options->all());
    }

    public function testGetOption(): void
    {
        $composer = Mockery::mock(Composer::class);
        $composer->shouldReceive('getPackage->getExtra');
        $options = new Options($composer);

        $this->assertSame('bin', $options->get('bin-dir'));
        $this->assertNull($options->get('non-existent-option'));
    }
}
