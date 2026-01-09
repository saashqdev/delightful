<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Command\Flow;

use App\Application\Flow\Service\DelightfulFlowExportImportAppService;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use Delightful\CloudFile\Kernel\Exceptions\CloudFileException;
use Delightful\CloudFile\Kernel\Struct\UploadFile;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

/**
 * @Command
 */
#[Command]
class ExportAgentWithFlowCommand extends HyperfCommand
{
    protected ContainerInterface $container;

    protected DelightfulFlowExportImportAppService $exportImportService;

    protected DelightfulAgentDomainService $agentDomainService;

    protected FileDomainService $fileDomainService;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
        $this->exportImportService = $this->container->get(DelightfulFlowExportImportAppService::class);
        $this->agentDomainService = $this->container->get(DelightfulAgentDomainService::class);
        $this->fileDomainService = $this->container->get(FileDomainService::class);
        parent::__construct('agent:export');
        $this->setDescription('Export agent to OSS (including main flow, tools, sub-flows, etc.)');
        $this->addArgument('agent_id', InputArgument::REQUIRED, 'Agent ID');
    }

    /**
     * @throws CloudFileException
     */
    public function handle()
    {
        $agentId = $this->input->getArgument('agent_id');

        // get助理info
        $agent = $this->agentDomainService->getById($agentId);

        $flowCode = $agent->getFlowCode();
        if (empty($flowCode)) {
            $this->output->error('助理nothaveassociate的process');
            return 1;
        }

        // from助理实bodymiddlegetorganizationcode和userID
        $orgCode = $agent->getOrganizationCode();
        $userId = $agent->getCreatedUid();

        // createdata隔离object
        $dataIsolation = new FlowDataIsolation($orgCode, $userId);

        // exportprocess及助理info
        $exportData = $this->exportImportService->exportFlowWithAgent($dataIsolation, $flowCode, $agent);

        // 将datasave为temporaryfile
        $filename = "agent-export-{$agentId}-" . time() . '.json';
        $tempFile = tempnam(sys_get_temp_dir(), 'flow_export_');
        file_put_contents($tempFile, json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        chmod($tempFile, 0644);
        // uploadtoOSS
        $uploadDir = $orgCode . '/open/' . md5(StorageBucketType::Public->value);
        $uploadFile = new UploadFile($tempFile, $uploadDir, $filename);

        // use已have的fileserviceupload
        try {
            // 定义uploaddirectory
            $subDir = 'open';

            // createuploadfileobject（not自动重命名）
            $uploadFile = new UploadFile($tempFile, $subDir, '', false);

            // uploadfile（finger定not自动createdirectory）
            $this->fileDomainService->uploadByCredential($orgCode, $uploadFile);

            // generate可access的link
            $fileLink = $this->fileDomainService->getLink($orgCode, $uploadFile->getKey(), StorageBucketType::Private);

            if ($fileLink) {
                // use这typemethodpoint击link是valid的link
                return 0;
            }

            $this->output->error('generatefilelinkfail');
            return 1;
        } catch (Throwable $e) {
            $this->output->error("uploadfilefail: {$e->getMessage()}");
            return 1;
        } finally {
            // deletetemporaryfile
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            // 释放uploadfile资源
            $uploadFile->release();
        }
    }
}
