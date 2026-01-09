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
        $this->setDescription('从OSS导入助理（包含主流程、工具、子流程等）');
        $this->addArgument('file_url', InputArgument::REQUIRED, '导出助理数据文件的URL');
        $this->addArgument('user_id', InputArgument::REQUIRED, 'userid');
        $this->addArgument('organization_code', InputArgument::REQUIRED, 'organization编码');
    }

    public function handle()
    {
        $fileUrl = $this->input->getArgument('file_url');

        // 下载文件content
        try {
            $client = new Client();
            $response = $client->get($fileUrl);
            $content = $response->getBody()->getContents();

            // 解析JSONcontent
            $importData = json_decode($content, true);
            if (! $importData || ! is_array($importData)) {
                $this->output->error('文件中的JSON数据无效');
                return 1;
            }

            // 从导入数据中getorganization代码和userID
            $orgCode = $this->input->getArgument('organization_code');
            $userId = $this->input->getArgument('user_id');

            if (empty($orgCode) || empty($userId)) {
                $this->output->error('导入数据中缺少organization代码或userID');
                return 1;
            }

            // create数据隔离object
            $dataIsolation = new FlowDataIsolation($orgCode, $userId);

            // 导入流程及助理info
            $result = $this->exportImportService->importFlowWithAgent($dataIsolation, $importData);
            $this->output->success('助理导入success。' . $result['agent_name']);
            return 0;
        } catch (Throwable $e) {
            $this->output->error("导入助理fail: {$e->getMessage()}");
            return 1;
        }
    }
}
