<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Check;

interface CheckInterface
{
    public function getName(): string;

    public function getDescription(): string;

    public function getMaxAnomalies(): int;

    public function getSchedule(): string;

    /**
     * Empty array = uses global recipients from the bundle configuration.
     *
     * @return string[]
     */
    public function getRecipients(): array;

    public function run(): CheckResult;
}
