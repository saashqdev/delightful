<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Command;

use App\Domain\Agent\Constant\InstructDisplayType;
use App\Domain\Agent\Constant\InstructType;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Agent\Repository\Persistence\DelightfulAgentRepository;
use App\Domain\Agent\Repository\Persistence\DelightfulAgentVersionRepository;
use Hyperf\Codec\Json;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

#[Command]
class EnsureSwitchResidencyCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container, public DelightfulAgentRepository $agentRepository, public DelightfulAgentVersionRepository $agentVersionRepository)
    {
        parent::__construct('agent:ensure-switch-residency');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('ensure haveassistantswitchfingercommandallhave residency=true property')
            ->addOption('test', 't', InputOption::VALUE_OPTIONAL, 'testmodetype:provideJSONformattestdataconductprocess', '')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'emptyrunlinemodetype:onlycheckbutnotupdatetodatabase');
    }

    public function handle()
    {
        // checkwhetherrunlineintestmodetype
        $testData = $this->input->getOption('test');
        $isDryRun = $this->input->getOption('dry-run');

        if (! empty($testData)) {
            return $this->handleTestMode($testData, $isDryRun);
        }

        if ($isDryRun) {
            $this->output->writeln('<info>runlineinemptyrunlinemodetype,willnotwillactualupdatedatabase</info>');
        }

        $batchSize = 20;
        $offset = 0;
        $total = 0;
        $updated = 0;

        $this->output->writeln('startprocessassistantswitchfingercommand...');

        while (true) {
            // minutebatchgetassistant
            $agents = $this->agentRepository->getAgentsByBatch($offset, $batchSize);
            if (empty($agents)) {
                break;
            }
            foreach ($agents as $agent) {
                ++$total;
                // getcurrentfingercommand
                $instructs = $agent['instructs'] ?? [];
                if (empty($instructs)) {
                    continue;
                }

                // checkandfixswitchfingercommand residency property
                $hasChanges = $this->ensureSwitchResidency($instructs);

                // iffingercommandhavechange,saveupdate
                if ($hasChanges) {
                    if (! $isDryRun) {
                        $this->agentRepository->updateInstruct(
                            $agent['organization_code'],
                            $agent['id'],
                            $instructs
                        );
                    }
                    ++$updated;
                    $this->output->writeln(sprintf('already%sassistant [%s] switchfingercommand', $isDryRun ? 'detecttoneedupdate' : 'update', $agent['id']));
                }
            }

            $offset += $batchSize;
            $this->output->writeln(sprintf('alreadyprocess %d assistant...', $total));
        }

        $this->output->writeln(sprintf(
            'processcomplete!sharedprocess %d assistant,%s %d assistantswitchfingercommand',
            $total,
            $isDryRun ? 'hairshowneedupdate' : 'update',
            $updated
        ));

        // processassistantversion
        $offset = 0;
        $versionTotal = 0;
        $versionUpdated = 0;

        $this->output->writeln('\nstartprocessassistantversionswitchfingercommand...');

        while (true) {
            // minutebatchgetassistantversion
            $versions = $this->agentVersionRepository->getAgentVersionsByBatch($offset, $batchSize);
            if (empty($versions)) {
                break;
            }

            foreach ($versions as $version) {
                ++$versionTotal;

                // getcurrentfingercommand
                $instructs = $version['instructs'] ?? [];
                if (empty($instructs)) {
                    continue;
                }

                // checkandfixswitchfingercommand residency property
                $hasChanges = $this->ensureSwitchResidency($instructs);

                // iffingercommandhavechange,saveupdate
                if ($hasChanges) {
                    if (! $isDryRun) {
                        $this->agentVersionRepository->updateById(
                            new DelightfulAgentVersionEntity(array_merge($version, ['instructs' => $instructs]))
                        );
                    }
                    ++$versionUpdated;
                    $this->output->writeln(sprintf('already%sassistantversion [%s] switchfingercommand', $isDryRun ? 'detecttoneedupdate' : 'update', $version['id']));
                }
            }

            $offset += $batchSize;
            $this->output->writeln(sprintf('alreadyprocess %d assistantversion...', $versionTotal));
        }

        $this->output->writeln(sprintf(
            'processcomplete!sharedprocess %d assistantversion,%s %d assistantversionswitchfingercommand',
            $versionTotal,
            $isDryRun ? 'hairshowneedupdate' : 'update',
            $versionUpdated
        ));
    }

    /**
     * processtestmodetype.
     *
     * @param string $testData JSONformattestdata
     * @param bool $isDryRun whetherforemptyrunlinemodetype
     */
    private function handleTestMode(string $testData, bool $isDryRun): int
    {
        $this->output->writeln('<info>runlineintestmodetype</info>');

        try {
            $data = Json::decode($testData);
        } catch (Throwable $e) {
            $this->output->writeln('<error>parsetestdatafail: ' . $e->getMessage() . '</error>');
            return 1;
        }

        $this->output->writeln('testdataprocessstart...');

        // displayoriginalfingercommand
        $this->output->writeln('<comment>originalfingercommand:</comment>');
        $this->output->writeln(Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // checkandfixswitchfingercommand residency property
        $hasChanges = $this->ensureSwitchResidency($data);

        // displayprocessresult
        $this->output->writeln('<comment>processbackfingercommand:</comment>');
        $this->output->writeln(Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->output->writeln(sprintf(
            'processcomplete!fingercommandcollection%supdate',
            $hasChanges ? 'already' : 'noneed'
        ));

        return 0;
    }

    /**
     * ensureswitchfingercommandallhave residency=true property.
     * @param array &$instructs fingercommandarray
     * @return bool whetherhavemodify
     */
    private function ensureSwitchResidency(array &$instructs): bool
    {
        $hasChanges = false;

        foreach ($instructs as &$group) {
            if (! isset($group['items']) || ! is_array($group['items'])) {
                continue;
            }

            foreach ($group['items'] as &$item) {
                // skipsystemfingercommandprocess
                if (isset($item['display_type']) && (int) $item['display_type'] === InstructDisplayType::SYSTEM) {
                    continue;
                }

                // checkwhetherisswitchfingercommand(type = 2)
                if (isset($item['type']) && (int) $item['type'] === InstructType::SWITCH->value) {
                    // ifnothave residency property,add residency = true
                    if (! isset($item['residency'])) {
                        $item['residency'] = true;
                        $hasChanges = true;
                        $this->output->writeln(sprintf(
                            'hairshowswitchfingercommand [%s](%s) missing residency property,alreadyadd',
                            $item['name'] ?? 'notnaming',
                            $item['id'] ?? 'noID'
                        ));
                    }
                }
            }
        }

        return $hasChanges;
    }
}
