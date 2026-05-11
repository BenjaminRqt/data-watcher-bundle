<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;
use BenjaminRqt\DataWatcherBundle\Entity\DataWatcherRun;
use BenjaminRqt\DataWatcherBundle\Notifier\EmailNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CheckRunner
{
    public function __construct(
        private EmailNotifier $notifier,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public function run(CheckInterface $check): DataWatcherRun
    {
        $run = new DataWatcherRun($check->getName());
        $startTime = microtime(true);

        $this->logger->info('[DataWatcher] Starting check "{name}"', ['name' => $check->getName()]);

        try {
            $result = $check->run();
            $ms = (microtime(true) - $startTime) * 1000;

            if ($result->hasAnomalies && $result->count >= $check->getMaxAnomalies()) {
                $run->markAsAnomaly($result->count, $result->message, $result->rows);
                $run->setExecutionTimeMs($ms);

                $this->logger->warning('[DataWatcher] {count} anomaly(ies) for "{name}"', [
                    'name'  => $check->getName(),
                    'count' => $result->count,
                ]);

                $this->notifier->notify($check, $result);
            } else {
                $run->markAsSuccess();
                $run->setExecutionTimeMs($ms);

                $this->logger->info('[DataWatcher] OK "{name}" ({ms}ms)', [
                    'name' => $check->getName(),
                    'ms'   => round($ms, 2),
                ]);
            }
        } catch (\Throwable $e) {
            $run->markAsError($e->getMessage());
            $run->setExecutionTimeMs((microtime(true) - $startTime) * 1000);

            $this->logger->error('[DataWatcher] Error "{name}" : {error}', [
                'name'      => $check->getName(),
                'error'     => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        $this->em->persist($run);
        $this->em->flush();

        return $run;
    }
}
