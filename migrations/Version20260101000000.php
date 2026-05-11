<?php

declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the data_watcher_run table for the check history.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE data_watcher_run (
                id               INT AUTO_INCREMENT NOT NULL,
                check_name       VARCHAR(255) NOT NULL,
                status           VARCHAR(20)  NOT NULL,
                anomaly_count    INT          NOT NULL DEFAULT 0,
                message          LONGTEXT              DEFAULT NULL,
                rows_sample      JSON                  DEFAULT NULL,
                error_message    LONGTEXT              DEFAULT NULL,
                executed_at      DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                execution_time_ms DOUBLE PRECISION     DEFAULT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL
        );

        $this->addSql('CREATE INDEX idx_check_name  ON data_watcher_run (check_name)');
        $this->addSql('CREATE INDEX idx_executed_at ON data_watcher_run (executed_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE data_watcher_run');
    }
}
