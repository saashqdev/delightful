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
        $this->setDescription('initializeAIcan力data(fromconfigurationfile同todatalibrary)');
        $this->addArgument('organization_code', InputArgument::REQUIRED, 'organizationencoding');
    }

    public function handle(): void
    {
        $this->aiAbilityAppService = $this->container->get(AiAbilityAppService::class);

        $organizationCode = $this->input->getArgument('organization_code');
        if (empty($organizationCode)) {
            $this->error('请provideorganizationencoding');
            return;
        }

        $this->info("startfororganization {$organizationCode} initializeAIcan力data...");

        try {
            // createonetemporary Authorization objectuseatcommandline
            $authorization = new DelightfulUserAuthorization();
            $authorization->setOrganizationCode($organizationCode);

            $count = $this->aiAbilityAppService->initializeAbilities($authorization);
            $this->info("successinitialize {$count} AIcan力");
        } catch (Throwable $e) {
            $this->error('initializeAIcan力datafailed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return;
        }

        $this->info('AIcan力datainitializecomplete');
    }
}
