<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Check;

abstract class AbstractCallableCheck implements CheckInterface
{
    public function getMaxAnomalies(): int
    {
        return 1;
    }

    public function getRecipients(): array
    {
        return [];
    }

    /**
     * Callable to execute.
     * Must return ArrayCallableInterface containing the anomalous rows — if no rows: no anomaly.
     *
     * @example [
     *  ['id' => 42],
     * ]
     */
    abstract protected function getCallable(): ArrayCallableInterface;

    /**
     * Columns to display in the email (if empty: all array columns).
     *
     * @return array<string>
     */
    protected function getDisplayColumns(): array
    {
        return [];
    }

    public function run(): CheckResult
    {
        /** @var array<array<string, scalar|null>> $rows */
        $rows = $this->getCallable()();

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
