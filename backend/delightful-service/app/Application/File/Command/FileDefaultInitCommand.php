<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\File\Command;

use App\Domain\File\Constant\DefaultFileBusinessType;
use App\Domain\File\Constant\DefaultFileType;
use App\Domain\File\Entity\DefaultFileEntity;
use App\Domain\File\Repository\Persistence\CloudFileRepository;
use App\Domain\File\Service\DefaultFileDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use Delightful\CloudFile\Kernel\Struct\UploadFile;
use Exception;
use Hyperf\Command\Command;
use Psr\Container\ContainerInterface;
use ValueError;

#[\Hyperf\Command\Annotation\Command]
class FileDefaultInitCommand extends Command
{
    protected ?string $name = 'file:init';

    protected ContainerInterface $container;

    protected FileDomainService $fileDomainService;

    protected DefaultFileDomainService $defaultFileDomainService;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('initializedefaultfile');
    }

    public function handle(): void
    {
        $this->fileDomainService = $this->container->get(FileDomainService::class);
        $this->defaultFileDomainService = $this->container->get(DefaultFileDomainService::class);

        // get公have桶configuration
        $publicBucketConfig = config('cloudfile.storages.' . StorageBucketType::Public->value);
        $this->line('公have桶configuration：' . json_encode($publicBucketConfig, JSON_UNESCAPED_UNICODE));

        // if是 local 驱动，notneedinitialize
        if ($publicBucketConfig['adapter'] === 'local') {
            $this->info('本ground驱动，notneedinitialize');
            return;
        }

        // executefileinitialize
        $this->initFiles();

        $this->info('file系统initializecomplete');
    }

    /**
     * initialize所havefile.
     */
    protected function initFiles(): void
    {
        $this->line('startinitializefile...');

        // 基础filedirectory - usenewpath结构
        $baseFileDir = BASE_PATH . '/storage/files';
        $defaultModulesDir = $baseFileDir . '/DELIGHTFUL/open/default';

        // checkdefault模piecedirectorywhether存in
        if (! is_dir($defaultModulesDir)) {
            $this->error('default模piecedirectorynot存in: ' . $defaultModulesDir);
            return;
        }

        $totalFiles = 0;
        $skippedFiles = 0;
        $organizationCode = CloudFileRepository::DEFAULT_ICON_ORGANIZATION_CODE;

        // get所have模piecedirectory
        $moduleDirs = array_filter(glob($defaultModulesDir . '/*'), 'is_dir');

        if (empty($moduleDirs)) {
            $this->warn('nothave找to任何模piecedirectory');
            return;
        }

        $this->line('handle模piecefile:');

        // 遍历each模piecedirectory
        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);

            try {
                // 尝试将模piece名mappingto对应的业务type
                $businessType = $this->mapModuleToBusinessType($moduleName);

                if ($businessType === null) {
                    $this->warn("  - skip未知模piece: {$moduleName}");
                    continue;
                }

                $this->line("  - handle模piece: {$moduleName} (业务type: {$businessType->value})");

                // get该模piecedirectorydown的所havefile
                $files = array_filter(glob($moduleDir . '/*'), 'is_file');

                if (empty($files)) {
                    $this->line('    - nothave找to任何file');
                    continue;
                }

                $fileCount = 0;

                // handleeachfile
                foreach ($files as $filePath) {
                    $fileName = basename($filePath);
                    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $fileSize = filesize($filePath);

                    // generate业务唯一标识（useat重复check）
                    $businessIdentifier = $moduleName . '/' . $fileName;

                    // correct的重复check：querysame业务typedownwhetherhavesame的业务标识
                    $existingFiles = $this->defaultFileDomainService->getByOrganizationCodeAndBusinessType($businessType, $organizationCode);
                    $isDuplicate = false;
                    foreach ($existingFiles as $existingFile) {
                        // use userId fieldstorage业务标识来判断重复
                        if ($existingFile->getUserId() === $businessIdentifier) {
                            $isDuplicate = true;
                            break;
                        }
                    }

                    if ($isDuplicate) {
                        $this->line("    - skip重复file: {$fileName}");
                        ++$skippedFiles;
                        continue;
                    }

                    $this->line("    - handlefile: {$fileName}");

                    try {
                        // readfilecontent并转为 base64 format
                        $fileContent = file_get_contents($filePath);
                        $mimeType = mime_content_type($filePath) ?: 'image/png';
                        $base64Content = 'data:' . $mimeType . ';base64,' . base64_encode($fileContent);

                        // 完all参考 ImageWatermarkProcessor 的success做法，butfinger定file名
                        $uploadFile = new UploadFile($base64Content, 'default-files', $fileName);
                        $this->fileDomainService->uploadByCredential(
                            $organizationCode,
                            $uploadFile,
                            StorageBucketType::Public
                        );

                        // 立即validatefilewhether可get（关键validate步骤）
                        $actualKey = $uploadFile->getKey();
                        // from key middle提取organizationencoding，参考 ProviderAppService 的correct做法
                        $keyOrganizationCode = substr($actualKey, 0, strpos($actualKey, '/'));
                        $fileLink = $this->fileDomainService->getLink($keyOrganizationCode, $actualKey, StorageBucketType::Public);
                        if (! $fileLink || ! $fileLink->getUrl()) {
                            throw new Exception('fileuploadfail，无法getaccesslink');
                        }

                        // validatesuccessback才createdatabaserecord，useactual的upload key
                        $defaultFileEntity = new DefaultFileEntity();
                        $defaultFileEntity->setBusinessType($businessType->value);
                        $defaultFileEntity->setFileType(DefaultFileType::DEFAULT->value);
                        $defaultFileEntity->setKey($actualKey);
                        $defaultFileEntity->setFileSize($fileSize);
                        $defaultFileEntity->setOrganization($organizationCode);
                        $defaultFileEntity->setFileExtension($fileExtension);
                        $defaultFileEntity->setUserId($businessIdentifier); // use业务标识作为 userId

                        // save实body
                        $this->defaultFileDomainService->insert($defaultFileEntity);

                        ++$fileCount;
                    } catch (Exception $e) {
                        $this->error("  - handlefile {$fileName} fail: {$e->getMessage()}");
                        continue; // not影响back续filehandle
                    }
                }

                $this->line("    - successhandle {$fileCount} file");
                $totalFiles += $fileCount;
            } catch (Exception $e) {
                $this->error("  - handle模piece {$moduleName} o clock出错: {$e->getMessage()}");
            }
        }

        // meanwhilehandleoriginal的defaultgraph标file（ifneed的话）
        $this->processDefaultIcons($baseFileDir, $organizationCode, $totalFiles, $skippedFiles);

        $this->info("fileinitializecomplete，共handle {$totalFiles} file，skip {$skippedFiles} 已存in的file");
    }

    /**
     * 将模piece名mappingto对应的业务type.
     */
    protected function mapModuleToBusinessType(string $moduleName): ?DefaultFileBusinessType
    {
        // 尝试直接mapping
        try {
            return DefaultFileBusinessType::from($moduleName);
        } catch (ValueError) {
            // if直接mappingfail，尝试passname匹配
            return match (strtolower($moduleName)) {
                'service_provider', 'serviceprovider', 'service-provider' => DefaultFileBusinessType::SERVICE_PROVIDER,
                'flow', 'workflow' => DefaultFileBusinessType::FLOW,
                'delightful', 'default' => DefaultFileBusinessType::Delightful,
                default => null,
            };
        }
    }

    /**
     * handledefaultgraph标file.
     */
    protected function processDefaultIcons(string $baseFileDir, string $organizationCode, int &$totalFiles, int &$skippedFiles): void
    {
        // ifhaveneed单独handle的defaultgraph标，canin这withinimplement
        // for examplehandle Midjourney etcdefaultgraph标
    }
}
