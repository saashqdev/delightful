<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Provider\Command;

use App\Application\Provider\Service\AiAbilityAppService;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

#[Command]
class InitAiAbilitiesCommand extends HyperfCommand
{
    protected ContainerInterface $container;

    protected AiAbilityAppService $aiAbilityAppService;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('ai-abilities:init');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('initializeAI能力data（从configurationfile同到datalibrary）');
        $this->addArgument('organization_code', InputArgument::REQUIRED, 'organizationencoding');
    }

    public function handle(): void
    {
        $this->aiAbilityAppService = $this->container->get(AiAbilityAppService::class);

        $organizationCode = $this->input->getArgument('organization_code');
        if (empty($organizationCode)) {
            $this->error('请提供organizationencoding');
            return;
        }

        $this->info("开始为organization {$organizationCode} initializeAI能力data...");

        try {
            // createonetemporary的 Authorization object用于命令行
            $authorization = new DelightfulUserAuthorization();
            $authorization->setOrganizationCode($organizationCode);

            $count = $this->aiAbilityAppService->initializeAbilities($authorization);
            $this->info("successinitialize {$count} 个AI能力");
        } catch (Throwable $e) {
            $this->error('initializeAI能力datafailed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return;
        }

        $this->info('AI能力datainitializecomplete');
    }
}
