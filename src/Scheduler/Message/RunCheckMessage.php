<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Scheduler\Message;

final readonly class RunCheckMessage
{
    public function __construct(
        public string $checkName,
    ) {
    }
}
