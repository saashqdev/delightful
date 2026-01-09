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

        // getassistantinfo
        $agent = $this->agentDomainService->getById($agentId);

        $flowCode = $agent->getFlowCode();
        if (empty($flowCode)) {
            $this->output->error('assistantnothaveassociateprocess');
            return 1;
        }

        // fromassistant实bodymiddlegetorganizationcodeanduserID
        $orgCode = $agent->getOrganizationCode();
        $userId = $agent->getCreatedUid();

        // createdataisolationobject
        $dataIsolation = new FlowDataIsolation($orgCode, $userId);

        // exportprocessandassistantinfo
        $exportData = $this->exportImportService->exportFlowWithAgent($dataIsolation, $flowCode, $agent);

        // willdatasavefortemporaryfile
        $filename = "agent-export-{$agentId}-" . time() . '.json';
        $tempFile = tempnam(sys_get_temp_dir(), 'flow_export_');
        file_put_contents($tempFile, json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        chmod($tempFile, 0644);
        // uploadtoOSS
        $uploadDir = $orgCode . '/open/' . md5(StorageBucketType::Public->value);
        $uploadFile = new UploadFile($tempFile, $uploadDir, $filename);

        // usealreadyhavefileserviceupload
        try {
            // definitionuploaddirectory
            $subDir = 'open';

            // createuploadfileobject(notfrom动rename)
            $uploadFile = new UploadFile($tempFile, $subDir, '', false);

            // uploadfile(finger定notfrom动createdirectory)
            $this->fileDomainService->uploadByCredential($orgCode, $uploadFile);

            // generatecanaccesslink
            $fileLink = $this->fileDomainService->getLink($orgCode, $uploadFile->getKey(), StorageBucketType::Private);

            if ($fileLink) {
                // usethistypemethodpoint击linkisvalidlink
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
            // releaseuploadfileresource
            $uploadFile->release();
        }
    }
}
