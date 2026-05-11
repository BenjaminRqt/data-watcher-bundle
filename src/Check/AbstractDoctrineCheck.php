<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Check;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

abstract class AbstractDoctrineCheck implements CheckInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $em,
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
     * Builds and returns the Doctrine Query.
     * It must return the anomalous entities or arrays.
     */
    abstract protected function buildQuery(): Query;

    /**
     * Converts a result (entity or array) to an associative array for the email.
     *
     * @return array<string, scalar|null>
     */
    protected function rowToArray(mixed $row): array
    {
        if (is_array($row)) {
            return $row;
        }

        if (is_object($row)) {
            $result = [];
            foreach (get_object_vars($row) as $key => $value) {
                if ($value instanceof \DateTimeInterface) {
                    $result[$key] = $value->format('Y-m-d H:i:s');
                } elseif (is_scalar($value) || $value === null) {
                    $result[$key] = $value;
                }
            }

            return $result;
        }

        return ['value' => (string)$row];
    }

    /**
     * Columns to display in the email (if empty: all).
     *
     * @return array<int, string>
     */
    protected function getDisplayColumns(): array
    {
        return [];
    }

    public function run(): CheckResult
    {
        $results = $this->buildQuery()->getResult();

        if (empty($results)) {
            return CheckResult::ok();
        }

        $rows = array_map(fn ($row) => $this->rowToArray($row), $results);

        return CheckResult::anomalies(
            rows: $rows,
            message: sprintf('%d abnormal record(s) detected.', count($rows)),
            columns: $this->getDisplayColumns(),
        );
    }
}
