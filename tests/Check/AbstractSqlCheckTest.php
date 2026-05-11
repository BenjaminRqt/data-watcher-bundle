<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests\Check;

use BenjaminRqt\DataWatcherBundle\Check\AbstractSqlCheck;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class AbstractSqlCheckTest extends TestCase
{
    public function testRunReturnsOkWhenNoResults(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with('SELECT * FROM test', [])
            ->willReturn([]);

        $check = new class($connection) extends AbstractSqlCheck {
            public function getName(): string { return 'test'; }
            public function getDescription(): string { return 'test desc'; }
            public function getSchedule(): string { return '* * * * *'; }
            protected function getSql(): string { return 'SELECT * FROM test'; }
        };

        $result = $check->run();

        $this->assertFalse($result->hasAnomalies);
        $this->assertEquals(0, $result->count);
    }

    public function testRunReturnsAnomaliesWhenResultsFound(): void
    {
        $connection = $this->createMock(Connection::class);

        $results = [
            ['id' => 1, 'name' => 'Anomaly 1'],
        ];

        $connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($results);

        $check = new class($connection) extends AbstractSqlCheck {
            public function getName(): string { return 'test'; }
            public function getDescription(): string { return 'test desc'; }
            public function getSchedule(): string { return '* * * * *'; }
            protected function getSql(): string { return 'SELECT * FROM test'; }
        };

        $result = $check->run();

        $this->assertTrue($result->hasAnomalies);
        $this->assertEquals(1, $result->count);
        $this->assertEquals($results, $result->rows);
    }
}
