<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests\Scheduler\Handler;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;
use BenjaminRqt\DataWatcherBundle\CheckRegistry;
use BenjaminRqt\DataWatcherBundle\CheckRunner;
use BenjaminRqt\DataWatcherBundle\Scheduler\Handler\RunCheckHandler;
use BenjaminRqt\DataWatcherBundle\Scheduler\Message\RunCheckMessage;
use PHPUnit\Framework\TestCase;

class RunCheckHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $check = $this->createMock(CheckInterface::class);
        $check->method('getName')->willReturn('test-check');

        $registry = new CheckRegistry([$check]);
        $runner = $this->createMock(CheckRunner::class);

        $runner->expects($this->once())
            ->method('run')
            ->with($check);

        $handler = new RunCheckHandler($registry, $runner);
        $message = new RunCheckMessage('test-check');

        $handler($message);
    }
}
