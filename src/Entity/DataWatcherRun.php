<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Entity;

class DataWatcherRun
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ANOMALY = 'anomaly';
    public const STATUS_ERROR   = 'error';

    private ?int $id = null;

    private string $checkName;

    private string $status;

    private int $anomalyCount;

    private ?string $message = null;

    /**
     * @var array<int, array<string, scalar|null>>|null
     */
    private ?array $rowsSample = null;

    private ?string $errorMessage = null;

    private \DateTimeImmutable $executedAt;

    private ?float $executionTimeMs = null;

    public function __construct(string $checkName)
    {
        $this->checkName    = $checkName;
        $this->status       = self::STATUS_SUCCESS;
        $this->anomalyCount = 0;
        $this->executedAt   = new \DateTimeImmutable();
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCheckName(): string
    {
        return $this->checkName;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAnomalyCount(): int
    {
        return $this->anomalyCount;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return array<int, array<string, scalar|null>>|null
     */
    public function getRowsSample(): ?array
    {
        return $this->rowsSample;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getExecutedAt(): \DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function setExecutionTimeMs(float $ms): self
    {
        $this->executionTimeMs = $ms;

        return $this;
    }

    public function getExecutionTimeMs(): ?float
    {
        return $this->executionTimeMs;
    }

    public function markAsSuccess(): self
    {
        $this->status = self::STATUS_SUCCESS;
        $this->anomalyCount = 0;

        return $this;
    }

    /**
     * @param array<int, array<string, scalar|null>> $rowsSample
     */
    public function markAsAnomaly(int $count, string $message, array $rowsSample = []): self
    {
        $this->status = self::STATUS_ANOMALY;
        $this->anomalyCount = $count;
        $this->message = $message;
        $this->rowsSample = array_slice($rowsSample, 0, 10);

        return $this;
    }

    public function markAsError(string $errorMessage): self
    {
        $this->status = self::STATUS_ERROR;
        $this->anomalyCount = 0;
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function hasAnomalies(): bool
    {
        return $this->status === self::STATUS_ANOMALY;
    }

    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }
}
