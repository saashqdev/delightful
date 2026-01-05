<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\AsyncEvent\Kernel\Command;

use Delightful\AsyncEvent\Kernel\AsyncEventRetry;
use Delightful\AsyncEvent\Kernel\Crontab\RetryCrontab;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class RetryCommand extends HyperfCommand
{
    protected ?string $name = 'async-event:retry';

    protected string $description = 'Retry specified async event';

    public function handle(): void
    {
        $id = $this->input->getArgument('id');
        if (! $id) {
            $this->error('Please provide event ID');
            return;
        }
        $id = (int) $id;
        if ($id === 1) {
            make(RetryCrontab::class)->execute();
            return;
        }
        AsyncEventRetry::retry($id);
        $this->info("Retry event {$id} has been triggered");
    }

    protected function getArguments(): array
    {
        return [
            ['id', InputArgument::REQUIRED, 'Event ID to retry'],
        ];
    }
}
