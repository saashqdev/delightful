<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Listener;

use Hyperf\Collection\Arr;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

#[Listener]
class DbQueryExecutedListener implements ListenerInterface
{
    private LoggerInterface $logger;

    // Tables excluded from logging
    private array $excludedTables = [
        'async_event_records',
    ];

    // Sensitive tables
    private array $sensitiveTables = [
        'delightful_chat_messages',
        'delightful_chat_message_versions',
        'delightful_flow_memory_histories',
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('sql');
    }

    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event): void
    {
        if ($event instanceof QueryExecuted) {
            $sql = $event->sql;

            // Check if the query involves excluded tables
            if ($this->isExcludedTable($sql)) {
                return;
            }

            // Only log the first 1024 characters
            $sql = substr($sql, 0, 1024);

            if (! Arr::isAssoc($event->bindings)) {
                $position = 0;
                foreach ($event->bindings as $value) {
                    $position = strpos($sql, '?', $position);
                    if ($position === false) {
                        break;
                    }
                    $value = "'{$value}'";
                    $sql = substr_replace($sql, $value, $position, 1);
                    $position += strlen($value);
                }
            }

            // Redact SQL for sensitive tables
            $sql = $this->desensitizeSql($sql);
            $this->logger->info(sprintf('[%s:%s] %s', $event->connectionName, $event->time, $sql));
        }
    }

    /**
     * Redact SQL touching sensitive tables.
     * 1. INSERT: keep the id column value, replace all other values with '***'.
     * 2. UPDATE: replace modified column values with '***'.
     */
    private function desensitizeSql(string $sql): string
    {
        // Check if this is touching a sensitive table
        $isSensitive = false;
        foreach ($this->sensitiveTables as $table) {
            // Use strict table name matching
            $pattern = '/\b' . preg_quote($table, '/') . '\b/i';
            if (preg_match($pattern, $sql)) {
                $isSensitive = true;
                break;
            }
        }

        // Not a sensitive table—no redaction needed
        if (! $isSensitive) {
            return $sql;
        }

        // Case-insensitive regex for statement type
        // Handle INSERT statements
        if (preg_match('/\binsert\s+into\b/i', $sql)) {
            // Keep the id value and replace all other values with '***'
            $pattern = '/values\s*(\((?:[^)(]+|(?1))*\))/i';
            if (preg_match($pattern, $sql, $matches) && ! empty($matches[1])) {
                $values = $matches[1];
                // Support single or multiple value lists
                if (preg_match('/^\(([^,]+)(,.+)?\)$/i', $values, $valueMatches)) {
                    // Assume the first column is id
                    $idValue = trim($valueMatches[1]);
                    $replacement = '(' . $idValue . ', ***)';
                    $sql = preg_replace($pattern, 'VALUES ' . $replacement, $sql, 1);
                } else {
                    // If parsing fails, redact the full value list
                    $sql = preg_replace($pattern, '(***)', $sql, 1);
                }
            } else {
                // If VALUES is not matched, try a fallback format
                $pattern = '/\bvalues\b\s*(\((?:[^)(]+|(?1))*\))/i';
                if (preg_match($pattern, $sql, $matches) && ! empty($matches[1])) {
                    $sql = preg_replace($pattern, 'VALUES (***)', $sql, 1);
                }
            }
        }

        // Handle UPDATE statements
        if (preg_match('/\bupdate\b/i', $sql) && preg_match('/\bset\b/i', $sql)) {
            // Simplify redaction when JSON data is present
            if (preg_match('/json|[{}\[\]":]/', $sql)) {
                // Split SQL to keep table and WHERE
                if (preg_match('/\bupdate\b\s+(`?\w+`?(?:\.\w+)?)\s+\bset\b/i', $sql, $tableMatches) && ! empty($tableMatches[1])) {
                    $tableName = $tableMatches[1];
                    $whereClause = '';
                    if (preg_match('/\bwhere\b(.*?)$/is', $sql, $whereMatches)) {
                        $whereClause = ' WHERE' . $whereMatches[1];
                    }
                    // For JSON payloads, return a simplified redacted SQL while keeping table and WHERE
                    return "UPDATE {$tableName} SET [complex JSON data redacted]{$whereClause}";
                }
                // Fallback: fully redact when parsing fails
                return 'UPDATE [table name] SET [complex data redacted]';
            }

            // Extract the SET clause in a resilient way
            $pattern = '/\bset\b(.*?)(?:\bwhere\b|$)/is';
            if (preg_match($pattern, $sql, $setMatches)) {
                $setClause = $setMatches[1];
                $originalSetClause = $setClause;

                // Safely split and replace assignments inside SET
                $fieldPattern = '/(`?\w+`?(?:\.\w+)?)\s*=\s*(?:\'(?:[^\'\\\]|\\\.)*\'|"(?:[^"\\\]|\\\.)*"|[^,\s]+)(?:,|$)/is';
                if (preg_match_all($fieldPattern, $setClause, $fieldMatches)) {
                    foreach ($fieldMatches[0] as $index => $match) {
                        $fieldName = $fieldMatches[1][$index];
                        $replacement = $fieldName . " = '***'";
                        // Preserve trailing commas when present
                        if (str_ends_with(trim($match), ',')) {
                            $replacement .= ',';
                        }
                        $setClause = str_replace($match, $replacement, $setClause);
                    }
                    $sql = str_replace($originalSetClause, $setClause, $sql);
                } else {
                    // Fallback when regex parsing fails
                    if (preg_match('/\bupdate\b\s+(`?\w+`?(?:\.\w+)?)/i', $sql, $tableMatches) && ! empty($tableMatches[1])) {
                        $tableName = $tableMatches[1];
                        $whereClause = '';
                        if (preg_match('/\bwhere\b(.*?)$/is', $sql, $whereMatches)) {
                            $whereClause = ' WHERE' . $whereMatches[1];
                        }
                        $sql = "UPDATE {$tableName} SET [data redacted]{$whereClause}";
                    }
                }
            }
        }

        // Handle SELECT statements and redact sensitive payloads
        if (preg_match('/\bselect\b/i', $sql)) {
            foreach ($this->sensitiveTables as $table) {
                if (preg_match('/\b' . preg_quote($table, '/') . '\b/i', $sql)) {
                    // Mark sensitive queries without showing details
                    return "SELECT [sensitive data] FROM {$table} [query redacted]";
                }
            }
        }

        return $sql;
    }

    /**
     * Check if the SQL query involves excluded tables.
     * Tables are wrapped with backticks in SQL, e.g., `table_name`.
     */
    private function isExcludedTable(string $sql): bool
    {
        if (empty($this->excludedTables)) {
            return false;
        }

        // Check for table name wrapped with backticks: `table_name`
        return array_any($this->excludedTables, fn ($table) => stripos($sql, "`{$table}`") !== false);
    }
}
