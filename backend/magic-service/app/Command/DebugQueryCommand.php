<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

#[Command]
class DebugQueryCommand extends HyperfCommand
{
    protected ?string $name = 'debug:query';

    public function configure()
    {
        parent::configure();
        $this->setDescription('Query the record count for a table');
        $this->addArgument('table', InputArgument::REQUIRED, 'Table name');
    }

    public function handle()
    {
        $table = $this->input->getArgument('table');

        try {
            $count = Db::table($table)->count();
            $this->line("Record count in table {$table}: {$count}");

            // Output a few rows from the table
            if ($count > 0) {
                $records = Db::table($table)->limit(5)->get();
                $this->table(['Field', 'Value'], $this->formatRecords($records));
            }
        } catch (Throwable $e) {
            $this->error('Query failed: ' . $e->getMessage());
        }
    }

    private function formatRecords($records)
    {
        $result = [];

        if (! empty($records) && count($records) > 0) {
            $record = $records[0];
            foreach ((array) $record as $field => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $result[] = [$field, $value];
            }
        }

        return $result;
    }
}
