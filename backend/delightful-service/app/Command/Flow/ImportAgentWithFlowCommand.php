<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Command\Flow;

use App\Application\Flow\Service\DelightfulFlowExportImportAppService;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use GuzzleHttp\Client;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

#[Command]
class ImportAgentWithFlowCommand extends HyperfCommand
{
    protected ContainerInterface $container;

    protected DelightfulFlowExportImportAppService $exportImportService;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
        $this->exportImportService = $container->get(DelightfulFlowExportImportAppService::class);
        parent::__construct('agent:import');
        $this->setDescription('从OSSimport助理（contain主process、tool、子process等）');
        $this->addArgument('file_url', InputArgument::REQUIRED, 'export助理datafile的URL');
        $this->addArgument('user_id', InputArgument::REQUIRED, 'userid');
        $this->addArgument('organization_code', InputArgument::REQUIRED, 'organizationencoding');
    }

    public function handle()
    {
        $fileUrl = $this->input->getArgument('file_url');

        // downloadfilecontent
        try {
            $client = new Client();
            $response = $client->get($fileUrl);
            $content = $response->getBody()->getContents();

            // parseJSONcontent
            $importData = json_decode($content, true);
            if (! $importData || ! is_array($importData)) {
                $this->output->error('file中的JSONdatainvalid');
                return 1;
            }

            // 从importdata中getorganizationcode和userID
            $orgCode = $this->input->getArgument('organization_code');
            $userId = $this->input->getArgument('user_id');

            if (empty($orgCode) || empty($userId)) {
                $this->output->error('importdata中缺少organizationcode或userID');
                return 1;
            }

            // createdata隔离object
            $dataIsolation = new FlowDataIsolation($orgCode, $userId);

            // importprocess及助理info
            $result = $this->exportImportService->importFlowWithAgent($dataIsolation, $importData);
            $this->output->success('助理importsuccess。' . $result['agent_name']);
            return 0;
        } catch (Throwable $e) {
            $this->output->error("import助理fail: {$e->getMessage()}");
            return 1;
        }
    }
}
