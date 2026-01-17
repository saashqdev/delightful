<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\SuperAgent\Command;

use Delightful\BeDelightful\Application\SuperAgent\Crontab\MessageCompensationCrontab;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

/**
 * Run Message Compensation Command
 * Command to manually run message compensation tasks.
 */
#[Command]
class RunMessageCompensationCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('superagent:compensation');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Run message compensation task manually');
        $this->addOption('loop', 'l', InputOption::VALUE_NONE, 'Run in loop mode (every 5 seconds)');
        $this->addOption('times', 't', InputOption::VALUE_OPTIONAL, 'Number of times to run (only in loop mode)', 10);
    }

    public function handle(): void
    {
        $messageCompensationCrontab = $this->container->get(MessageCompensationCrontab::class);

        $isLoop = $this->input->getOption('loop');
        $times = (int) $this->input->getOption('times');

        if ($isLoop) {
            $this->info('Starting loop mode for message compensation task...');
            $this->info("Will run {$times} times, with 5 second intervals");

            for ($i = 1; $i <= $times; ++$i) {
                $this->info("--- Run #{$i} ---");
                $startTime = microtime(true);

                try {
                    $messageCompensationCrontab->execute();
                    $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                    $this->info("Execution completed, time elapsed: {$executionTime}ms");
                } catch (Throwable $e) {
                    $this->error('Execution failed: ' . $e->getMessage());
                }

                if ($i < $times) {
                    $this->info('Waiting 5 seconds...');
                    sleep(5);
                }
            }

            $this->info('Loop execution completed!');
        } else {
            $this->info('Running message compensation task...');
            $startTime = microtime(true);

            try {
                $messageCompensationCrontab->execute();
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                $this->info("Task completed, time elapsed: {$executionTime}ms");
            } catch (Throwable $e) {
                $this->error('Task execution failed: ' . $e->getMessage());
            }
        }
    }
}
