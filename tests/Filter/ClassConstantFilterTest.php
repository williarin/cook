<?php

declare(strict_types=1);

namespace Williarin\Cook\Test\Filter;

use PHPUnit\Framework\TestCase;
use Williarin\Cook\Filter\ClassConstantFilter;

class ClassConstantFilterTest extends TestCase
{
    private ClassConstantFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new ClassConstantFilter();
    }

    public function testGetName(): void
    {
        $this->assertSame('class_constant', $this->filter::getName());
    }

    public function testProcess(): void
    {
        $this->assertSame('Williarin\Cook::class', $this->filter->process('Williarin\Cook', 'Williarin\Cook'));
    }

    public function testProcessWithSingleQuotedString(): void
    {
        $this->assertSame('Williarin\Cook::class', $this->filter->process("'Williarin\Cook'", 'Williarin\Cook'));
    }

    public function testProcessForNonString(): void
    {
        $this->assertSame([12], $this->filter->process([12], [12]));
    }
}
