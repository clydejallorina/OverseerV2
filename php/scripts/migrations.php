#!/usr/bin/env php
<?php

namespace Overseer\Scripts;

use DateTimeImmutable;
use DateTimeZone;
use Error;
use Exception;
use Overseer\DB\DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationsCommand extends Command {
    protected function configure()
    {
        $this->setDefinition([
            new InputOption(
                name: 'run',
                description: 'Run all non-migrated SQL queries. This is the default action.',
            ),
            new InputOption(
                name: 'count',
                description: 'Count all non-migrated SQL queries',
            ),
            new InputOption(
                name: 'identify',
                description: 'Identify all non-migrated SQL queries',
            ),
            new InputOption(
                name: 'check',
                description: 'Check if the migration database table exists, and report how many migrations have been processed already',
            ),
        ]);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $formatter = new FormatterHelper();

        $output->writeln($formatter->formatSection('SQL Migrations', 'Running Overseer SQL Migrations'));
        
        try {
            if ($input->getOption('count')) {
                $this->count($output);
            }
            else if ($input->getOption('identify')) {
                $this->identify($output);
            }
            else if ($input->getOption('check')) {
                $this->check($output);
            }
            else {
                $this->runMigrations($output);
            }
        } catch (Exception $e) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Uncaught exception detected!'));
            $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
        } catch (Error $e) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Uncaught error detected!'));
            $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
        }

        return 0;
    }

    private function runMigrations(OutputInterface $output): void
    {
        $formatter = new FormatterHelper();

        try {
            $db = new DB();
        } catch (Exception $e) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Could not access the DB due to the following exception.'));
            $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
            return;
        } catch (Error $e) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Could not access the DB due to the following error.'));
            $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
            return;
        }

        if (!$this->checkForMigrationTable($db)) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Could not find migration table, attempting to generate this table...'));
            try {
                $this->generateMigrationTable($db);
            } catch (Exception $e) {
                $output->writeln($formatter->formatSection('SQL Migrations', 'Could not create the migration table due to the following exception.'));
                $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
                return;
            } catch (Error $e) {
                $output->writeln($formatter->formatSection('SQL Migrations', 'Could not create the migration table due to the following error.'));
                $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
                return;
            }
            $output->writeln($formatter->formatSection('SQL Migrations', 'Migrations table successfully created.'));
        }

        $output->writeln($formatter->formatSection('SQL Migrations', 'Getting which migrations have been successfully done...'));
        $migrated = $this->getCurrentMigrations($db);
        $migratedCount = count($migrated);
        $output->writeln($formatter->formatSection('SQL Migrations', "Counted {$migratedCount} migration/s completed"));

        $output->writeln($formatter->formatSection('SQL Migrations', 'Getting which migrations need to be applied...'));
        $allMigrationsInPath = $this->getAllMigrationsInPath();
        $migratedFilenames = array_map(
            callback: fn (Migration $migration): string => $migration->filename,
            array: $migrated,
        );
        $awaiting = array_diff($allMigrationsInPath, $migratedFilenames);
        $awaitingCount = count($awaiting);
        $output->writeln($formatter->formatSection('SQL Migrations', "Counted {$awaitingCount} migration/s awaiting to be applied"));

        if ($awaitingCount === 0) {
            return;
        }

        $output->writeln($formatter->formatSection('SQL Migrations', 'Running the migrations...'));

        $progressBar = new ProgressBar($output, $awaitingCount);
        $progressBar->setFormat('verbose');
        $progressBar->start();

        foreach ($awaiting as $migrateMe)
        {
            $this->migrateFromSqlFile($migrateMe, $db);

            $progressBar->advance();
        }

        $progressBar->finish();

        $output->writeln('');

        $output->writeln($formatter->formatSection('SQL Migrations', 'Migrations applied!'));
    }

    private function count(OutputInterface $output): void
    {
        $formatter = new FormatterHelper();
        try {
            $db = new DB();
        } catch (Exception $e) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Could not access the DB due to the following exception.'));
            $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
            return;
        } catch (Error $e) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Could not access the DB due to the following error.'));
            $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
            return;
        }

        $output->writeln($formatter->formatSection('SQL Migrations', 'Getting which migrations need to be applied...'));
        $allMigrationsInPath = $this->getAllMigrationsInPath();
        $migrated = $this->checkForMigrationTable($db) ? $this->getCurrentMigrations($db) : [];
        $migratedFilenames = array_map(
            callback: fn (Migration $migration): string => $migration->filename,
            array: $migrated,
        );
        $awaiting = array_diff($allMigrationsInPath, $migratedFilenames);
        $awaitingCount = count($awaiting);
        $output->writeln($formatter->formatSection('SQL Migrations', "Counted {$awaitingCount} migration/s awaiting to be applied"));
    }

    private function identify(OutputInterface $output): void
    {
        $formatter = new FormatterHelper();
        try {
            $db = new DB();
        } catch (Exception $e) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Could not access the DB due to the following exception.'));
            $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
            return;
        } catch (Error $e) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Could not access the DB due to the following error.'));
            $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
            return;
        }

        $output->writeln($formatter->formatSection('SQL Migrations', 'Getting which migrations need to be applied...'));
        $allMigrationsInPath = $this->getAllMigrationsInPath();
        $migrated = $this->checkForMigrationTable($db) ? $this->getCurrentMigrations($db) : [];
        $migratedFilenames = array_map(
            callback: fn (Migration $migration): string => $migration->filename,
            array: $migrated,
        );
        $awaiting = array_diff($allMigrationsInPath, $migratedFilenames);

        if (count($awaiting) === 0) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'No awaiting migrations found.'));
            return;
        }

        foreach ($awaiting as $migrateMe)
        {
            $output->writeln(' - ' . $migrateMe);
        }
    }

    private function check(OutputInterface $output)
    {
        $formatter = new FormatterHelper();
        try {
            $db = new DB();
        } catch (Exception $e) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Could not access the DB due to the following exception.'));
            $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
            return;
        } catch (Error $e) {
            $output->writeln($formatter->formatSection('SQL Migrations', 'Could not access the DB due to the following error.'));
            $output->writeln($formatter->formatBlock($e->getMessage(), 'error'));
            return;
        }

        if ($this->checkForMigrationTable($db)) {
            $migrated = $this->getCurrentMigrations($db);
            $migratedCount = count($migrated);
            $output->writeln($formatter->formatBlock("Migration table found with {$migratedCount} migration/s", 'info'));
            foreach ($migrated as $migration)
            {
                $appliedOn = $migration->appliedOn->format('Y-m-d H:i:s e');
                $output->writeln(" - {$migration->filename} (applied on {$appliedOn})");
            }
        } else {
            $output->writeln($formatter->formatBlock('Migration table not found!', 'comment'));
        }
    }

    /**
     * Get the current list of active migrations
     * @param DB $db
     * @return list<Migration>
     */
    private function getCurrentMigrations(DB $db): array
    {
        return $db->fetchAll(
            sqlQuery: <<<'SQL'
                SELECT * FROM migrations;
                SQL,
            values: [],
            returnClass: Migration::class,
            customFieldConverters: [
                fn (int $appliedOn): DateTimeImmutable => DateTimeImmutable::createFromFormat('U', (string)$appliedOn),
            ],
        );
    }

    /**
     * @return list<string>
     */
    private function getAllMigrationsInPath(): array
    {
        return array_filter(
            array: scandir(Migration::DIRECTORY),
            callback: fn (string $file): bool => str_ends_with($file, '.sql'),
        );
    }

    private function checkForMigrationTable(DB $db): bool
    {
        $tableName = Migration::TABLE_NAME;

        return $db->exists(
            sqlQuery: <<<"SQL"
                SELECT * FROM {$tableName} LIMIT 1;
                SQL,
        );
    }

    private function generateMigrationTable(DB $db): void
    {
        $tableName = Migration::TABLE_NAME;

        $db->execute(sqlQuery: <<<"SQL"
            CREATE TABLE IF NOT EXISTS {$tableName} (
                `filename` VARCHAR(256) NOT NULL PRIMARY KEY COMMENT 'Filename of the migration file applied',
                `appliedOn` INT(8) NOT NULL COMMENT 'ISO / Epoch timestamp of when this was applied'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            SQL,
        );
    }

    private function migrateFromSqlFile(string $filename, DB $db): void
    {
        $filepath = Migration::DIRECTORY . $filename;

        // Run the actual migration
        $migrationScript = file_get_contents($filepath);
        if ($migrationScript === false) {
            $realPath = realpath($filepath);
            throw new Exception("Failed to get file contents for {$realPath}");
        }
        $db->execute($migrationScript);

        // Save to migration table
        $tableName = Migration::TABLE_NAME;
        // Maybe this could be time() instead but I want to be sure that this is in UTC just in case
        $currentDateTime = new DateTimeImmutable(timezone: new DateTimeZone("UTC"));
        $db->insert(
            sqlQuery: <<<"SQL"
                INSERT INTO {$tableName} (`filename`, appliedOn) VALUES (?, ?)
                SQL,
            values: [
                $filename,
                $currentDateTime->getTimestamp(),
            ],
        );
    }
}

/**
 * Migration object for proper type hinting of migration data
 */
final class Migration
{
    // In the Docker container, it's expected for the migrations folder to be in /var/www/migrations/
    const DIRECTORY = '/var/www/migrations/';
    const TABLE_NAME = 'migrations';

    public function __construct(
        /** Filename of the migration script */
        public string $filename,
        /** Date and time when the migration was applied */
        public DateTimeImmutable $appliedOn,
    ) {}
}
