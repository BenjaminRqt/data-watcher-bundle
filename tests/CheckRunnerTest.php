<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;
use BenjaminRqt\DataWatcherBundle\Check\CheckResult;
use BenjaminRqt\DataWatcherBundle\CheckRunner;
use BenjaminRqt\DataWatcherBundle\Notifier\EmailNotifier;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CheckRunnerTest extends TestCase
{
    private EmailNotifier $notifier;
    private Connection $connection;
    private LoggerInterface $logger;
    private CheckRunner $runner;

    protected function setUp(): void
    {
        $this->notifier = $this->createMock(EmailNotifier::class);
        $this->connection = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->runner = new CheckRunner($this->notifier, $this->connection, $this->logger);
    }

    public function testRunSuccess(): void
    {
        $check = $this->createMock(CheckInterface::class);
        $check->method('getName')->willReturn('test-check');
        $check->method('run')->willReturn(CheckResult::ok());

        $this->connection->expects($this->once())->method('insert')->with('data_watcher_run', $this->isType('array'));

        $run = $this->runner->run($check);

        $this->assertTrue($run->isSuccess());
        $this->assertEquals('test-check', $run->getCheckName());
        $this->assertEquals(0, $run->getAnomalyCount());
    }

    public function testRunAnomalyWithNotification(): void
    {
        $check = $this->createMock(CheckInterface::class);
        $check->method('getName')->willReturn('anomaly-check');
        $check->method('getMaxAnomalies')->willReturn(1);
        
        $result = CheckResult::anomalies([['id' => 1]], 'Anomaly!');
        $check->method('run')->willReturn($result);

        $this->notifier->expects($this->once())
            ->method('notify')
            ->with($check, $result);

        $this->connection->expects($this->once())->method('insert');

        $run = $this->runner->run($check);

        $this->assertTrue($run->hasAnomalies());
        $this->assertEquals(1, $run->getAnomalyCount());
        $this->assertEquals('Anomaly!', $run->getMessage());
    }

    public function testRunAnomalyBelowThresholdNoNotification(): void
    {
        $check = $this->createMock(CheckInterface::class);
        $check->method('getName')->willReturn('threshold-check');
        $check->method('getMaxAnomalies')->willReturn(5);
        
        $result = CheckResult::anomalies([['id' => 1]], '1 anomalie');
        $check->method('run')->willReturn($result);

        $this->notifier->expects($this->never())->method('notify');
        $this->connection->expects($this->once())->method('insert');

        $run = $this->runner->run($check);

        $this->assertTrue($run->isSuccess()); // Marqué comme success car en dessous du seuil
    }

    public function testRunError(): void
    {
        $check = $this->createMock(CheckInterface::class);
        $check->method('getName')->willReturn('error-check');
        $check->method('run')->willThrowException(new \RuntimeException('Big problem'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error "{name}"'), $this->anything());

        $this->connection->expects($this->once())->method('insert');

        $run = $this->runner->run($check);

        $this->assertTrue($run->hasError());
        $this->assertEquals('Big problem', $run->getErrorMessage());
    }
}
