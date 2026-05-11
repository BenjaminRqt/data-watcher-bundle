<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Check;

use Doctrine\DBAL\Connection;

abstract class AbstractSqlCheck implements CheckInterface
{
    public function __construct(
        protected readonly Connection $connection,
    ) {
    }

    public function getMaxAnomalies(): int
    {
        return 1;
    }

    public function getRecipients(): array
    {
        return [];
    }

    /**
     * SQL query to execute.
     * Must return the anomalous rows — if no rows: no anomaly.
     */
    abstract protected function getSql(): string;

    /**
     * Parameters to bind in the query.
     *
     * @return array<string, mixed>
     */
    protected function getParameters(): array
    {
        return [];
    }

    /**
     * Columns to display in the email (if empty: all columns from the SELECT).
     *
     * @return array<string>
     */
    protected function getDisplayColumns(): array
    {
        return [];
    }

    public function run(): CheckResult
    {
        /** @var array<int, array<string, scalar|null>> $rows */
        $rows = $this->connection->fetchAllAssociative(
            $this->getSql(),
            $this->getParameters(),
        );

        if (empty($rows)) {
            return CheckResult::ok();
        }

        return CheckResult::anomalies(
            rows: $rows,
            message: sprintf('%d abnormal record(s) detected.', count($rows)),
            columns: $this->getDisplayColumns(),
        );
    }
}
