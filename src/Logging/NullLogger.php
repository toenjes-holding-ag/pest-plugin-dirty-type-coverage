<?php

declare(strict_types=1);

namespace Pest\DirtyTypeCoverage\Logging;

use Pest\DirtyTypeCoverage\Contracts\Logger;

/**
 * @internal
 */
final class NullLogger implements Logger
{
    /**
     * {@inheritDoc}
     */
    public function append(string $path, array $uncoveredLines, array $uncoveredLinesIgnored, float $percentage): void
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function output(): void
    {
        //
    }
}
