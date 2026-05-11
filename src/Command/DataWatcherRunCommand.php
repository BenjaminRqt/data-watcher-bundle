<?php declare(strict_types=1);

namespace BenjaminRqt\DataWatcherBundle\Command;

use BenjaminRqt\DataWatcherBundle\CheckRegistry;
use BenjaminRqt\DataWatcherBundle\CheckRunner;
use BenjaminRqt\DataWatcherBundle\Entity\DataWatcherRun;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'data-watcher:run',
    description: 'Run one or all DataWatcher checks',
)]
final class DataWatcherRunCommand extends Command
{
    public function __construct(
        private readonly CheckRegistry $registry,
        private readonly CheckRunner $runner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('check', InputArgument::OPTIONAL, 'Name of the check to run (empty = all)')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List available checks')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Display results without sending emails or saving history');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');

        $io->title('🔍 DataWatcher');

        if ($input->getOption('list')) {
            $this->listChecks($io);

            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $io->note('Dry-run mode: no email sent, no history recorded.');
        }

        $checkName = $input->getArgument('check');

        // ── Single check ────────────────────────────────────────────────────
        if ($checkName) {
            if (!$this->registry->has($checkName)) {
                $io->error(sprintf('Check "%s" not found.', $checkName));
                $this->listChecks($io);

                return Command::FAILURE;
            }

            $check = $this->registry->get($checkName);

            if ($isDryRun) {
                $result = $check->run();
                if ($result->hasAnomalies) {
                    $io->warning(sprintf('%d anomaly(ies) for "%s"', $result->count, $checkName));
                    $firstRow = $result->rows[0] ?? [];
                    $columns = $result->columns ?: array_keys($firstRow);
                    $io->table($columns, array_map(function ($row) use ($columns) {
                        $filtered = [];
                        foreach ($columns as $col) {
                            $filtered[$col] = $row[$col] ?? null;
                        }

                        return array_values($filtered);
                    }, $result->rows));
                } else {
                    $io->success(sprintf('No anomaly for "%s"', $checkName));
                }

                return Command::SUCCESS;
            }

            $run = $this->runner->run($check);
            $this->printRun($io, $run);

            return $run->hasError() ? Command::FAILURE : Command::SUCCESS;
        }

        // ── All checks ──────────────────────────────────────────────────
        $checks = $this->registry->all();

        if (empty($checks)) {
            $io->warning('No check registered.');

            return Command::SUCCESS;
        }

        $io->section(sprintf('Running %d check(s)...', count($checks)));
        $hasFailures = false;

        foreach ($checks as $check) {
            $io->write(sprintf('  → <info>%s</info> ... ', $check->getName()));

            if ($isDryRun) {
                $result = $check->run();
                $output->writeln($result->hasAnomalies
                    ? sprintf('<comment>⚠ %d anomaly(ies)</comment>', $result->count)
                    : '<fg=green>✓ OK</>');
                continue;
            }

            $run = $this->runner->run($check);
            match (true) {
                $run->hasAnomalies() => $output->writeln(sprintf('<comment>⚠ %d anomaly(ies) — email sent</comment>', $run->getAnomalyCount())),
                $run->hasError()     => $output->writeln(sprintf('<error>✗ %s</error>', $run->getErrorMessage())),
                default              => $output->writeln('<fg=green>✓ OK</>'),
            };

            if ($run->hasError()) {
                $hasFailures = true;
            }
        }

        $io->newLine();
        $hasFailures ? $io->warning('Errors were detected.') : $io->success('All checks passed.');

        return $hasFailures ? Command::FAILURE : Command::SUCCESS;
    }

    private function listChecks(SymfonyStyle $io): void
    {
        $checks = $this->registry->all();
        if (empty($checks)) {
            $io->warning('No check registered.');

            return;
        }
        $io->table(
            ['Name', 'Description', 'Schedule', 'Recipients'],
            array_map(fn ($c) => [
                $c->getName(),
                $c->getDescription(),
                $c->getSchedule(),
                $c->getRecipients() ? implode(', ', $c->getRecipients()) : '(global config)',
            ], $checks),
        );
    }

    private function printRun(SymfonyStyle $io, DataWatcherRun $run): void
    {
        match (true) {
            $run->hasAnomalies() => $io->warning(sprintf('⚠ %d anomaly(ies) for "%s". Email sent.', $run->getAnomalyCount(), $run->getCheckName())),
            $run->hasError()     => $io->error(sprintf('Error: %s', $run->getErrorMessage())),
            default              => $io->success(sprintf('No anomaly for "%s".', $run->getCheckName())),
        };
    }
}
