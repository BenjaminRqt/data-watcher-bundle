<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;
use BenjaminRqt\DataWatcherBundle\Check\CheckResult;
use BenjaminRqt\DataWatcherBundle\CheckRunner;
use BenjaminRqt\DataWatcherBundle\Entity\DataWatcherRun;
use BenjaminRqt\DataWatcherBundle\Notifier\EmailNotifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CheckRunnerTest extends TestCase
{
    private EmailNotifier $notifier;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private CheckRunner $runner;

    protected function setUp(): void
    {
        $this->notifier = $this->createMock(EmailNotifier::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->runner = new CheckRunner($this->notifier, $this->em, $this->logger);
    }

    public function testRunSuccess(): void
    {
        $check = $this->createMock(CheckInterface::class);
        $check->method('getName')->willReturn('test-check');
        $check->method('run')->willReturn(CheckResult::ok());

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(DataWatcherRun::class));
        $this->em->expects($this->once())->method('flush');

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

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

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

        $run = $this->runner->run($check);

        $this->assertTrue($run->hasError());
        $this->assertEquals('Big problem', $run->getErrorMessage());
    }
}
