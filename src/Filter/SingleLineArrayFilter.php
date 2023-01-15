<?php

declare(strict_types=1);

namespace Williarin\Cook\Filter;

use Symfony\Component\VarExporter\VarExporter;

final class SingleLineArrayFilter implements Filter
{
    public static function getName(): string
    {
        return 'single_line_array';
    }

    public function process(mixed $value, mixed $originalValue = null): mixed
    {
        if (!\is_array($originalValue)) {
            return $value;
        }

        return preg_replace(
            ['/\[\s*/', '/,\n\s*]/', '/,\n\s*(?!])/'],
            ['[', ']', ', '],
            VarExporter::export($originalValue),
        );
    }
}
