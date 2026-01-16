<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Command;

use Delightful\BeDelightful\Application\SuperAgent\Crontab\MessageCompensationCrontab;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\including er\including erInterface;
use Symfony\Component\Console\Input\InputOption;
use Throwable;
/** * Run Message Compensation Command * ManualRowMessagecompensation Task. */ #[Command]

class RunMessageCompensationCommand extends HyperfCommand 
{
 
    public function __construct(
    protected including erInterface $container) 
{
 parent::__construct('superagent:compensation'); 
}
 
    public function configure(): void 
{
 parent::configure(); $this->setDescription('Run message compensation task manually'); $this->addOption('loop', 'l', InputOption::VALUE_NONE, 'Run in loop mode (every 5 seconds)'); $this->addOption('times', 't', InputOption::VALUE_OPTIONAL, 'Number of times to run (only in loop mode)', 10); 
}
 
    public function handle(): void 
{
 $messageCompensationCrontab = $this->container->get(MessageCompensationCrontab::class); $isLoop = $this->input->getOption('loop'); $times = (int) $this->input->getOption('times'); if ($isLoop) 
{
 $this->info('Start running message compensation task loop...'); $this->info( Row 
{
$times
}
 interval 5 seconds each time ); for ($i = 1; $i <= $times; ++$i) 
{
 $this->info( --- 
{
$i
}
 Row --- ); $startTime = microtime(true); try 
{
 $messageCompensationCrontab->execute(); $executionTime = round((microtime(true) - $startTime) * 1000, 2); $this->info( Execution completed, time taken: 
{
$executionTime
}
ms ); 
}
 catch (Throwable $e) 
{
 $this->error('Execution failed: ' . $e->getMessage()); 
}
 if ($i < $times) 
{
 $this->info('Waiting 5 seconds...'); sleep(5); 
}
 
}
 $this->info('loop Rowcomplete '); 
}
 else 
{
 $this->info('RowMessagecompensation Task...'); $startTime = microtime(true); try 
{
 $messageCompensationCrontab->execute(); $executionTime = round((microtime(true) - $startTime) * 1000, 2); $this->info( TaskExecution completed, time taken: 
{
$executionTime
}
ms ); 
}
 catch (Throwable $e) 
{
 $this->error('TaskExecution failed: ' . $e->getMessage()); 
}
 
}
 
}
 
}
 
