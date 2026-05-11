<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Check;

final readonly class CheckResult
{
    /**
     * @param array<int, array<string, scalar|null>> $rows
     * @param array<string> $columns
     */
    private function __construct(
        public bool $hasAnomalies,
        public int $count,
        public array $rows = [],
        public string $message = '',
        public array $columns = [],
    ) {
    }

    public static function ok(): self
    {
        return new self(false, 0, [], 'No anomaly detected.');
    }

    /**
     * @param array<int, array<string, scalar|null>> $rows
     * @param array<string> $columns
     */
    public static function anomalies(array $rows, string $message = '', array $columns = []): self
    {
        return new self(
            hasAnomalies: true,
            count: count($rows),
            rows: $rows,
            message: $message,
            columns: $columns,
        );
    }
}
