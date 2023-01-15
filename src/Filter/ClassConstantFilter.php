<?php

declare(strict_types=1);

namespace Williarin\Cook\Filter;

final class ClassConstantFilter implements Filter
{
    public static function getName(): string
    {
        return 'class_constant';
    }

    public function process(mixed $value, mixed $originalValue = null): mixed
    {
        if (!\is_string($originalValue)) {
            return $value;
        }

        return $originalValue . '::class';
    }
}
