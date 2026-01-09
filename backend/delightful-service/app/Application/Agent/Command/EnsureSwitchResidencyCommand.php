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
        $this->setDescription('ensure所have助理switchfinger令allhave residency=true property')
            ->addOption('test', 't', InputOption::VALUE_OPTIONAL, 'test模type：provideJSONformattestdataconductprocess', '')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'empty运line模type：只checkbutnotupdatetodatabase');
    }

    public function handle()
    {
        // checkwhether运lineintest模type
        $testData = $this->input->getOption('test');
        $isDryRun = $this->input->getOption('dry-run');

        if (! empty($testData)) {
            return $this->handleTestMode($testData, $isDryRun);
        }

        if ($isDryRun) {
            $this->output->writeln('<info>运lineinempty运line模type，willnotwillactualupdatedatabase</info>');
        }

        $batchSize = 20;
        $offset = 0;
        $total = 0;
        $updated = 0;

        $this->output->writeln('startprocess助理switchfinger令...');

        while (true) {
            // minutebatchget助理
            $agents = $this->agentRepository->getAgentsByBatch($offset, $batchSize);
            if (empty($agents)) {
                break;
            }
            foreach ($agents as $agent) {
                ++$total;
                // getcurrentfinger令
                $instructs = $agent['instructs'] ?? [];
                if (empty($instructs)) {
                    continue;
                }

                // checkand修复switchfinger令 residency property
                $hasChanges = $this->ensureSwitchResidency($instructs);

                // iffinger令havechange，saveupdate
                if ($hasChanges) {
                    if (! $isDryRun) {
                        $this->agentRepository->updateInstruct(
                            $agent['organization_code'],
                            $agent['id'],
                            $instructs
                        );
                    }
                    ++$updated;
                    $this->output->writeln(sprintf('already%s助理 [%s] switchfinger令', $isDryRun ? 'detecttoneedupdate' : 'update', $agent['id']));
                }
            }

            $offset += $batchSize;
            $this->output->writeln(sprintf('alreadyprocess %d 助理...', $total));
        }

        $this->output->writeln(sprintf(
            'processcomplete！共process %d 助理，%s %d 助理switchfinger令',
            $total,
            $isDryRun ? 'hair现needupdate' : 'update',
            $updated
        ));

        // process助理version
        $offset = 0;
        $versionTotal = 0;
        $versionUpdated = 0;

        $this->output->writeln('\nstartprocess助理versionswitchfinger令...');

        while (true) {
            // minutebatchget助理version
            $versions = $this->agentVersionRepository->getAgentVersionsByBatch($offset, $batchSize);
            if (empty($versions)) {
                break;
            }

            foreach ($versions as $version) {
                ++$versionTotal;

                // getcurrentfinger令
                $instructs = $version['instructs'] ?? [];
                if (empty($instructs)) {
                    continue;
                }

                // checkand修复switchfinger令 residency property
                $hasChanges = $this->ensureSwitchResidency($instructs);

                // iffinger令havechange，saveupdate
                if ($hasChanges) {
                    if (! $isDryRun) {
                        $this->agentVersionRepository->updateById(
                            new DelightfulAgentVersionEntity(array_merge($version, ['instructs' => $instructs]))
                        );
                    }
                    ++$versionUpdated;
                    $this->output->writeln(sprintf('already%s助理version [%s] switchfinger令', $isDryRun ? 'detecttoneedupdate' : 'update', $version['id']));
                }
            }

            $offset += $batchSize;
            $this->output->writeln(sprintf('alreadyprocess %d 助理version...', $versionTotal));
        }

        $this->output->writeln(sprintf(
            'processcomplete！共process %d 助理version，%s %d 助理versionswitchfinger令',
            $versionTotal,
            $isDryRun ? 'hair现needupdate' : 'update',
            $versionUpdated
        ));
    }

    /**
     * processtest模type.
     *
     * @param string $testData JSONformattestdata
     * @param bool $isDryRun whetherforempty运line模type
     */
    private function handleTestMode(string $testData, bool $isDryRun): int
    {
        $this->output->writeln('<info>运lineintest模type</info>');

        try {
            $data = Json::decode($testData);
        } catch (Throwable $e) {
            $this->output->writeln('<error>parsetestdatafail: ' . $e->getMessage() . '</error>');
            return 1;
        }

        $this->output->writeln('testdataprocessstart...');

        // displayoriginalfinger令
        $this->output->writeln('<comment>originalfinger令:</comment>');
        $this->output->writeln(Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // checkand修复switchfinger令 residency property
        $hasChanges = $this->ensureSwitchResidency($data);

        // displayprocessresult
        $this->output->writeln('<comment>processbackfinger令:</comment>');
        $this->output->writeln(Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->output->writeln(sprintf(
            'processcomplete！finger令collection%supdate',
            $hasChanges ? 'already' : 'no需'
        ));

        return 0;
    }

    /**
     * ensureswitchfinger令allhave residency=true property.
     * @param array &$instructs finger令array
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
                // skipsystemfinger令process
                if (isset($item['display_type']) && (int) $item['display_type'] === InstructDisplayType::SYSTEM) {
                    continue;
                }

                // checkwhetherisswitchfinger令(type = 2)
                if (isset($item['type']) && (int) $item['type'] === InstructType::SWITCH->value) {
                    // ifnothave residency property，add residency = true
                    if (! isset($item['residency'])) {
                        $item['residency'] = true;
                        $hasChanges = true;
                        $this->output->writeln(sprintf(
                            'hair现switchfinger令 [%s](%s) 缺少 residency property，alreadyadd',
                            $item['name'] ?? 'not命名',
                            $item['id'] ?? 'noID'
                        ));
                    }
                }
            }
        }

        return $hasChanges;
    }
}
