<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Scheduler\Handler;

use BenjaminRqt\DataWatcherBundle\CheckRegistry;
use BenjaminRqt\DataWatcherBundle\CheckRunner;
use BenjaminRqt\DataWatcherBundle\Scheduler\Message\RunCheckMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RunCheckHandler
{
    public function __construct(
        private CheckRegistry $registry,
        private CheckRunner $runner,
    ) {
    }

    public function __invoke(RunCheckMessage $message): void
    {
        $check = $this->registry->get($message->checkName);
        $this->runner->run($check);
    }
}
