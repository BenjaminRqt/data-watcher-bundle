<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Tests\Scheduler;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;
use BenjaminRqt\DataWatcherBundle\CheckRegistry;
use BenjaminRqt\DataWatcherBundle\Scheduler\DataWatcherSchedule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Contracts\Cache\CacheInterface;

class DataWatcherScheduleTest extends TestCase
{
    public function testGetSchedule(): void
    {
        $check = $this->createMock(CheckInterface::class);
        $check->method('getName')->willReturn('test-check');
        $check->method('getSchedule')->willReturn('0 0 * * *');

        $registry = new CheckRegistry([$check]);
        $cache = $this->createMock(CacheInterface::class);

        $provider = new DataWatcherSchedule($registry, $cache);
        $schedule = $provider->getSchedule();

        $messages = $schedule->getRecurringMessages();
        $this->assertCount(1, $messages);
        
        /** @var RecurringMessage $recurringMessage */
        $recurringMessage = $messages[0];
        $this->assertEquals('0 0 * * *', (string) $recurringMessage->getTrigger());
    }
}
