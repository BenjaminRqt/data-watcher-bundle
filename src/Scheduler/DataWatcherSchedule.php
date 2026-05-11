<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Scheduler;

use BenjaminRqt\DataWatcherBundle\CheckRegistry;
use BenjaminRqt\DataWatcherBundle\Scheduler\Message\RunCheckMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('data_watcher')]
final readonly class DataWatcherSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CheckRegistry $registry,
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();
        $schedule->stateful($this->cache);

        foreach ($this->registry->all() as $check) {
            $schedule->add(
                RecurringMessage::cron(
                    $check->getSchedule(),
                    new RunCheckMessage($check->getName()),
                )
            );
        }

        return $schedule;
    }
}
