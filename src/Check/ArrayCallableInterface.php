<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Check;

interface ArrayCallableInterface
{
    /**
     * @return array<array<string, scalar|null>>
     */
    public function __invoke(): array;
}
