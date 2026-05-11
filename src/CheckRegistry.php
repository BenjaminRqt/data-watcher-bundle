<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle;

use BenjaminRqt\DataWatcherBundle\Check\CheckInterface;

final class CheckRegistry
{
    /** @var CheckInterface[] */
    private array $checks = [];

    /**
     * @param iterable<CheckInterface> $checks Injected via !tagged_iterator data_watcher.check
     */
    public function __construct(iterable $checks)
    {
        foreach ($checks as $check) {
            $this->checks[$check->getName()] = $check;
        }
    }

    /** @return CheckInterface[] */
    public function all(): array
    {
        return $this->checks;
    }

    public function get(string $name): CheckInterface
    {
        if (!isset($this->checks[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Check "%s" introuvable. Checks disponibles : %s',
                $name,
                implode(', ', array_keys($this->checks)) ?: '(aucun)',
            ));
        }

        return $this->checks[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->checks[$name]);
    }

    /** @return string[] */
    public function getNames(): array
    {
        return array_keys($this->checks);
    }
}
