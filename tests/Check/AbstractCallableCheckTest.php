<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests\Check;

use BenjaminRqt\DataWatcherBundle\Check\AbstractCallableCheck;
use BenjaminRqt\DataWatcherBundle\Check\ArrayCallableInterface;
use PHPUnit\Framework\TestCase;

class AbstractCallableCheckTest extends TestCase
{
    public function testRunReturnsOkWhenNoResults(): void
    {
        $callable = $this->createMock(ArrayCallableInterface::class);
        $callable->expects($this->once())
            ->method('__invoke')
            ->willReturn([]);

        $check = new class($callable) extends AbstractCallableCheck {
            public function __construct(private readonly ArrayCallableInterface $callable) {}
            public function getName(): string { return 'test'; }
            public function getDescription(): string { return 'test desc'; }
            public function getSchedule(): string { return '* * * * *'; }
            protected function getCallable(): ArrayCallableInterface { return $this->callable; }
        };

        $result = $check->run();

        $this->assertFalse($result->hasAnomalies);
        $this->assertEquals(0, $result->count);
    }

    public function testRunReturnsAnomaliesWhenResultsFound(): void
    {
        $results = [['id' => 1]];
        $callable = $this->createMock(ArrayCallableInterface::class);
        $callable->expects($this->once())
            ->method('__invoke')
            ->willReturn($results);

        $check = new class($callable) extends AbstractCallableCheck {
            public function __construct(private readonly ArrayCallableInterface $callable) {}
            public function getName(): string { return 'test'; }
            public function getDescription(): string { return 'test desc'; }
            public function getSchedule(): string { return '* * * * *'; }
            protected function getCallable(): ArrayCallableInterface { return $this->callable; }
        };

        $result = $check->run();

        $this->assertTrue($result->hasAnomalies);
        $this->assertEquals(1, $result->count);
        $this->assertEquals($results, $result->rows);
    }
}
