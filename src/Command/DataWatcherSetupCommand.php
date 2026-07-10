<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Exception\TypesException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'data-watcher:setup',
    description: 'Create the database table for DataWatcher history',
)]
final class DataWatcherSetupCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    /**
     * @throws TypesException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $schemaManager = $this->connection->createSchemaManager();
        $tableName = 'data_watcher_run';

        if ($schemaManager->tablesExist([$tableName])) {
            $io->success(sprintf('Table "%s" already exists.', $tableName));

            return Command::SUCCESS;
        }

        $table = new Table($tableName);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('check_name', 'string', ['length' => 255]);
        $table->addColumn('status', 'string', ['length' => 20]);
        $table->addColumn('anomaly_count', 'integer', ['default' => 0]);
        $table->addColumn('message', 'text', ['notnull' => false]);
        $table->addColumn('rows_sample', 'json', ['notnull' => false]);
        $table->addColumn('error_message', 'text', ['notnull' => false]);
        $table->addColumn('executed_at', 'datetime');
        $table->addColumn('execution_time_ms', 'float', ['notnull' => false]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setUnquotedColumnNames('id')
                ->create(),
        );
        $table->addIndex(['check_name'], 'idx_dwrun_check_name');
        $table->addIndex(['executed_at'], 'idx_dwrun_executed_at');

        $schemaManager->createTable($table);

        $io->success(sprintf('Table "%s" has been created.', $tableName));

        return Command::SUCCESS;
    }
}
