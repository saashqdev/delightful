<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Provider\Command;

use App\Application\Provider\Service\AdminProviderAppService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Throwable;

#[Command]
class InitDelightfulProviderCommand extends HyperfCommand
{
    protected ContainerInterface $container;

    protected AdminProviderAppService $adminProviderAppService;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('delightful-provider:init');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('initializeDelightfulservice商configurationdata');
    }

    public function handle(): void
    {
        $this->adminProviderAppService = $this->container->get(AdminProviderAppService::class);

        $this->info('startinitializeDelightfulservice商configurationdata...');

        try {
            $count = $this->adminProviderAppService->initializeDelightfulProviderConfigs();
            $this->info("successinitialize {$count} 个service商configuration");
        } catch (Throwable $e) {
            $this->error('initializeDelightfulservice商configurationdatafailed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return;
        }

        $this->info('Delightfulservice商configurationdatainitializecomplete');
    }
}
