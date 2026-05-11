<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests\Entity;

use BenjaminRqt\DataWatcherBundle\Entity\DataWatcherRun;
use PHPUnit\Framework\TestCase;

class DataWatcherRunTest extends TestCase
{
    public function testInitialState(): void
    {
        $run = new DataWatcherRun('test-check');

        $this->assertEquals('test-check', $run->getCheckName());
        $this->assertEquals(DataWatcherRun::STATUS_SUCCESS, $run->getStatus());
        $this->assertEquals(0, $run->getAnomalyCount());
        $this->assertInstanceOf(\DateTimeImmutable::class, $run->getExecutedAt());
        $this->assertTrue($run->isSuccess());
    }

    public function testMarkAsAnomaly(): void
    {
        $run = new DataWatcherRun('test-check');
        $rows = array_fill(0, 15, ['id' => 1]);
        
        $run->markAsAnomaly(15, 'Anomalies detected', $rows);

        $this->assertEquals(DataWatcherRun::STATUS_ANOMALY, $run->getStatus());
        $this->assertEquals(15, $run->getAnomalyCount());
        $this->assertEquals('Anomalies detected', $run->getMessage());
        $this->assertCount(10, $run->getRowsSample()); // Vérifie le slice à 10
        $this->assertTrue($run->hasAnomalies());
    }

    public function testMarkAsError(): void
    {
        $run = new DataWatcherRun('test-check');
        $run->markAsError('Fatal error');

        $this->assertEquals(DataWatcherRun::STATUS_ERROR, $run->getStatus());
        $this->assertEquals('Fatal error', $run->getErrorMessage());
        $this->assertTrue($run->hasError());
    }

    public function testExecutionTime(): void
    {
        $run = new DataWatcherRun('test-check');
        $run->setExecutionTimeMs(123.45);

        $this->assertEquals(123.45, $run->getExecutionTimeMs());
    }
}
