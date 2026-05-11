<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests\Check;

use BenjaminRqt\DataWatcherBundle\Check\AbstractDoctrineCheck;
use BenjaminRqt\DataWatcherBundle\Check\CheckResult;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;

class AbstractDoctrineCheckTest extends TestCase
{
    public function testRunReturnsOkWhenNoResults(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $query = $this->createMock(Query::class, ['getResult'], [], '', false);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $check = new class($em, $query) extends AbstractDoctrineCheck {
            public function __construct(EntityManagerInterface $em, private readonly Query $query)
            {
                parent::__construct($em);
            }

            public function getName(): string { return 'test'; }
            public function getDescription(): string { return 'test desc'; }
            public function getSchedule(): string { return '* * * * *'; }
            protected function buildQuery(): Query
            {
                return $this->query;
            }
        };

        $result = $check->run();

        $this->assertFalse($result->hasAnomalies);
        $this->assertEquals(0, $result->count);
        $this->assertEquals('No anomaly detected.', $result->message);
    }

    public function testRunReturnsAnomaliesWhenResultsFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $query = $this->createMock(Query::class, ['getResult'], [], '', false);

        $results = [
            ['id' => 1, 'name' => 'Anomaly 1'],
            ['id' => 2, 'name' => 'Anomaly 2'],
        ];

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($results);

        $check = new class($em, $query) extends AbstractDoctrineCheck {
            public function __construct(EntityManagerInterface $em, private readonly Query $query)
            {
                parent::__construct($em);
            }

            public function getName(): string { return 'test'; }
            public function getDescription(): string { return 'test desc'; }
            public function getSchedule(): string { return '* * * * *'; }
            protected function buildQuery(): Query
            {
                return $this->query;
            }
        };

        $result = $check->run();

        $this->assertTrue($result->hasAnomalies);
        $this->assertEquals(2, $result->count);
        $this->assertCount(2, $result->rows);
        $this->assertEquals('2 abnormal record(s) detected.', $result->message);
    }

    public function testRowToArrayWithObject(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $query = $this->createMock(Query::class, ['getResult'], [], '', false);

        $obj = new \stdClass();
        $obj->id = 1;
        $obj->date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $obj->active = true;

        $query->method('getResult')->willReturn([$obj]);

        $check = new class($em, $query) extends AbstractDoctrineCheck {
            public function __construct(EntityManagerInterface $em, private readonly Query $query)
            {
                parent::__construct($em);
            }
            public function getName(): string { return 'test'; }
            public function getDescription(): string { return 'test desc'; }
            public function getSchedule(): string { return '* * * * *'; }
            protected function buildQuery(): Query { return $this->query; }
        };

        $result = $check->run();
        
        $expectedRow = [
            'id' => 1,
            'date' => '2023-01-01 12:00:00',
            'active' => true,
        ];
        
        $this->assertEquals($expectedRow, $result->rows[0]);
    }
}
