<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;
use BenjaminRqt\DataWatcherBundle\CheckRegistry;
use PHPUnit\Framework\TestCase;

class CheckRegistryTest extends TestCase
{
    public function testAllAndGet(): void
    {
        $check1 = $this->createMock(CheckInterface::class);
        $check1->method('getName')->willReturn('check1');
        
        $check2 = $this->createMock(CheckInterface::class);
        $check2->method('getName')->willReturn('check2');

        $registry = new CheckRegistry([$check1, $check2]);

        $this->assertCount(2, $registry->all());
        $this->assertSame($check1, $registry->get('check1'));
        $this->assertSame($check2, $registry->get('check2'));
    }

    public function testHas(): void
    {
        $check1 = $this->createMock(CheckInterface::class);
        $check1->method('getName')->willReturn('check1');

        $registry = new CheckRegistry([$check1]);

        $this->assertTrue($registry->has('check1'));
        $this->assertFalse($registry->has('unknown'));
    }

    public function testGetNames(): void
    {
        $check1 = $this->createMock(CheckInterface::class);
        $check1->method('getName')->willReturn('check1');
        
        $check2 = $this->createMock(CheckInterface::class);
        $check2->method('getName')->willReturn('check2');

        $registry = new CheckRegistry([$check1, $check2]);

        $this->assertEquals(['check1', 'check2'], $registry->getNames());
    }

    public function testGetThrowsExceptionIfNotFound(): void
    {
        $registry = new CheckRegistry([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Check "unknown" introuvable');
        
        $registry->get('unknown');
    }
}
