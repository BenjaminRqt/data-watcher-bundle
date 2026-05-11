<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests\Command;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;
use BenjaminRqt\DataWatcherBundle\Check\CheckResult;
use BenjaminRqt\DataWatcherBundle\CheckRegistry;
use BenjaminRqt\DataWatcherBundle\CheckRunner;
use BenjaminRqt\DataWatcherBundle\Command\DataWatcherRunCommand;
use BenjaminRqt\DataWatcherBundle\Entity\DataWatcherRun;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DataWatcherRunCommandTest extends TestCase
{
    private CheckRegistry $registry;
    private CheckRunner $runner;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->registry = new CheckRegistry([]);
        $this->runner = $this->createMock(CheckRunner::class);

        $command = new DataWatcherRunCommand($this->registry, $this->runner);
        $application = new Application();
        $application->addCommand($command);

        $this->commandTester = new CommandTester($application->find('data-watcher:run'));
    }

    public function testListChecks(): void
    {
        $check = new FakeCheck('test-check', 'Description test', '0 0 * * *');
        $this->registry = new CheckRegistry([$check]);
        
        // Re-setup with the populated registry
        $command = new DataWatcherRunCommand($this->registry, $this->runner);
        $application = new Application();
        $application->addCommand($command);
        $this->commandTester = new CommandTester($application->find('data-watcher:run'));

        $this->commandTester->execute(['--list' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('test-check', $output);
        $this->assertStringContainsString('Description test', $output);
        $this->assertStringContainsString('0 0 * * *', $output);
    }

    public function testExecuteSingleCheckNotFound(): void
    {
        $this->commandTester->execute(['check' => 'non-existent']);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Check "non-existent" not found', $this->commandTester->getDisplay());
    }

    public function testExecuteSingleCheckDryRunSuccess(): void
    {
        $check = new FakeCheck('test-check');
        $check->setResult(CheckResult::ok());
        
        $this->registry = new CheckRegistry([$check]);
        $command = new DataWatcherRunCommand($this->registry, $this->runner);
        $application = new Application();
        $application->addCommand($command);
        $this->commandTester = new CommandTester($application->find('data-watcher:run'));

        $this->commandTester->execute(['check' => 'test-check', '--dry-run' => true]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('No anomaly for "test-check"', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Dry-run mode', $this->commandTester->getDisplay());
    }

    public function testExecuteSingleCheckDryRunWithAnomalies(): void
    {
        $check = new FakeCheck('test-check');
        $check->setResult(CheckResult::anomalies([['id' => 1]], 'Anomaly detected'));
        
        $this->registry = new CheckRegistry([$check]);
        $command = new DataWatcherRunCommand($this->registry, $this->runner);
        $application = new Application();
        $application->addCommand($command);
        $this->commandTester = new CommandTester($application->find('data-watcher:run'));

        $this->commandTester->execute(['check' => 'test-check', '--dry-run' => true]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('1 anomaly(ies) for "test-check"', $this->commandTester->getDisplay());
        $this->assertStringContainsString('id', $this->commandTester->getDisplay());
    }

    public function testExecuteSingleCheckSuccess(): void
    {
        $check = new FakeCheck('test-check');
        $run = new DataWatcherRun('test-check');
        $run->markAsSuccess();

        $this->registry = new CheckRegistry([$check]);
        $this->runner->expects($this->once())
            ->method('run')
            ->with($check)
            ->willReturn($run);

        $command = new DataWatcherRunCommand($this->registry, $this->runner);
        $application = new Application();
        $application->addCommand($command);
        $this->commandTester = new CommandTester($application->find('data-watcher:run'));

        $this->commandTester->execute(['check' => 'test-check']);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('No anomaly for "test-check"', $this->commandTester->getDisplay());
    }

    public function testExecuteAllChecksSuccess(): void
    {
        $check1 = new FakeCheck('check1');
        $check2 = new FakeCheck('check2');
        $this->registry = new CheckRegistry([$check1, $check2]);

        $run1 = new DataWatcherRun('check1');
        $run1->markAsSuccess();
        $run2 = new DataWatcherRun('check2');
        $run2->markAsSuccess();

        $this->runner->expects($this->exactly(2))
            ->method('run')
            ->willReturnMap([
                [$check1, $run1],
                [$check2, $run2],
            ]);

        $command = new DataWatcherRunCommand($this->registry, $this->runner);
        $application = new Application();
        $application->addCommand($command);
        $this->commandTester = new CommandTester($application->find('data-watcher:run'));

        $this->commandTester->execute([]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('All checks passed', $this->commandTester->getDisplay());
    }

    public function testExecuteAllChecksWithFailure(): void
    {
        $check1 = new FakeCheck('check1');
        $check2 = new FakeCheck('check2');
        $this->registry = new CheckRegistry([$check1, $check2]);

        $run1 = new DataWatcherRun('check1');
        $run1->markAsSuccess();
        $run2 = new DataWatcherRun('check2');
        $run2->markAsError('An error occurred');

        $this->runner->expects($this->exactly(2))
            ->method('run')
            ->willReturnMap([
                [$check1, $run1],
                [$check2, $run2],
            ]);

        $command = new DataWatcherRunCommand($this->registry, $this->runner);
        $application = new Application();
        $application->addCommand($command);
        $this->commandTester = new CommandTester($application->find('data-watcher:run'));

        $this->commandTester->execute([]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Errors were detected', $this->commandTester->getDisplay());
        $this->assertStringContainsString('An error occurred', $this->commandTester->getDisplay());
    }
}

class FakeCheck implements CheckInterface
{
    private CheckResult $result;

    public function __construct(
        private readonly string $name = 'fake-check',
        private readonly string $description = 'Fake description',
        private readonly string $schedule = '* * * * *',
        private readonly array $recipients = [],
        private readonly int $maxAnomalies = 1
    ) {
        $this->result = CheckResult::ok();
    }

    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getSchedule(): string { return $this->schedule; }
    public function getRecipients(): array { return $this->recipients; }
    public function getMaxAnomalies(): int { return $this->maxAnomalies; }

    public function setResult(CheckResult $result): void
    {
        $this->result = $result;
    }

    public function run(): CheckResult
    {
        return $this->result;
    }
}
