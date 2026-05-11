<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'data_watcher_run')]
#[ORM\Index(name: 'idx_dwrun_check_name', columns: ['check_name'])]
#[ORM\Index(name: 'idx_dwrun_executed_at', columns: ['executed_at'])]
class DataWatcherRun
{
    public const string STATUS_SUCCESS = 'success';
    public const string STATUS_ANOMALY = 'anomaly';
    public const string STATUS_ERROR   = 'error';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'check_name', type: 'string', length: 255)]
    private string $checkName;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'integer')]
    private int $anomalyCount;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    /**
     * @var array<int, array<string, scalar|null>>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $rowsSample = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $executedAt;

    #[ORM\Column(type: 'float', nullable: true)]
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
