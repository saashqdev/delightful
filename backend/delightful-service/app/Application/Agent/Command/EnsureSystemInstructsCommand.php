<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Command;

use App\Domain\Agent\Constant\SystemInstructType;
use App\Domain\Agent\Entity\MagicAgentVersionEntity;
use App\Domain\Agent\Repository\Persistence\MagicAgentRepository;
use App\Domain\Agent\Repository\Persistence\MagicAgentVersionRepository;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

#[Command]
class EnsureSystemInstructsCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container, public MagicAgentRepository $agentRepository, public MagicAgentVersionRepository $agentVersionRepository)
    {
        parent::__construct('agent:ensure-system-instructs');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Ensure every assistant has complete system interaction prompts');
    }

    public function handle()
    {
        $batchSize = 20;
        $offset = 0;
        $total = 0;
        $updated = 0;

        $this->output->writeln('Starting assistant system interaction prompts...');

        while (true) {
            // Fetch assistants in batches
            $agents = $this->agentRepository->getAgentsByBatch($offset, $batchSize);
            if (empty($agents)) {
                break;
            }

            foreach ($agents as $agent) {
                ++$total;

                // Get the current prompts
                $instructs = $agent['instructs'] ?? [];

                // Validate and supplement system prompts
                $newInstructs = SystemInstructType::ensureSystemInstructs($instructs);

                // Persist changes when prompts differ
                if ($newInstructs !== $instructs) {
                    $this->agentRepository->updateInstruct(
                        $agent['organization_code'],
                        $agent['id'],
                        $newInstructs
                    );
                    ++$updated;
                    $this->output->writeln(sprintf('Updated assistant [%s] system interaction prompts', $agent['id']));
                }
            }

            $offset += $batchSize;
            $this->output->writeln(sprintf('Processed %d assistants so far...', $total));
        }

        $this->output->writeln(sprintf(
            'Done. Processed %d assistants and updated %d assistant system prompts',
            $total,
            $updated
        ));

        // 处理助理版本
        $offset = 0;
        $versionTotal = 0;
        $versionUpdated = 0;

        $this->output->writeln('Starting assistant version system interaction prompts...');

        while (true) {
            // Fetch assistant versions in batches
            $versions = $this->agentVersionRepository->getAgentVersionsByBatch($offset, $batchSize);
            if (empty($versions)) {
                break;
            }

            foreach ($versions as $version) {
                ++$versionTotal;

                // Get the current prompts
                $instructs = $version['instructs'] ?? [];

                // Validate and supplement system prompts
                $newInstructs = SystemInstructType::ensureSystemInstructs($instructs);

                // Persist changes when prompts differ
                if ($newInstructs !== $instructs) {
                    $this->agentVersionRepository->updateById(
                        new MagicAgentVersionEntity(array_merge($version, ['instructs' => $newInstructs]))
                    );
                    ++$versionUpdated;
                    $this->output->writeln(sprintf('Updated assistant version [%s] system interaction prompts', $version['id']));
                }
            }

            $offset += $batchSize;
            $this->output->writeln(sprintf('Processed %d assistant versions so far...', $versionTotal));
        }

        $this->output->writeln(sprintf(
            'Done. Processed %d assistant versions and updated %d assistant version system prompts',
            $versionTotal,
            $versionUpdated
        ));
    }
}
