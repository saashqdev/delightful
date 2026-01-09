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

        // get公有桶configuration
        $publicBucketConfig = config('cloudfile.storages.' . StorageBucketType::Public->value);
        $this->line('公有桶configuration：' . json_encode($publicBucketConfig, JSON_UNESCAPED_UNICODE));

        // 如果是 local 驱动，不needinitialize
        if ($publicBucketConfig['adapter'] === 'local') {
            $this->info('本地驱动，不needinitialize');
            return;
        }

        // executefileinitialize
        $this->initFiles();

        $this->info('file系统initializecomplete');
    }

    /**
     * initialize所有file.
     */
    protected function initFiles(): void
    {
        $this->line('startinitializefile...');

        // 基础file目录 - usenew路径结构
        $baseFileDir = BASE_PATH . '/storage/files';
        $defaultModulesDir = $baseFileDir . '/DELIGHTFUL/open/default';

        // checkdefault模块目录是否存在
        if (! is_dir($defaultModulesDir)) {
            $this->error('default模块目录不存在: ' . $defaultModulesDir);
            return;
        }

        $totalFiles = 0;
        $skippedFiles = 0;
        $organizationCode = CloudFileRepository::DEFAULT_ICON_ORGANIZATION_CODE;

        // get所有模块目录
        $moduleDirs = array_filter(glob($defaultModulesDir . '/*'), 'is_dir');

        if (empty($moduleDirs)) {
            $this->warn('没有找到任何模块目录');
            return;
        }

        $this->line('handle模块file:');

        // 遍历每个模块目录
        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);

            try {
                // 尝试将模块名映射到对应的业务type
                $businessType = $this->mapModuleToBusinessType($moduleName);

                if ($businessType === null) {
                    $this->warn("  - 跳过未知模块: {$moduleName}");
                    continue;
                }

                $this->line("  - handle模块: {$moduleName} (业务type: {$businessType->value})");

                // get该模块目录下的所有file
                $files = array_filter(glob($moduleDir . '/*'), 'is_file');

                if (empty($files)) {
                    $this->line('    - 没有找到任何file');
                    continue;
                }

                $fileCount = 0;

                // handle每个file
                foreach ($files as $filePath) {
                    $fileName = basename($filePath);
                    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $fileSize = filesize($filePath);

                    // generate业务唯一标识（用于重复check）
                    $businessIdentifier = $moduleName . '/' . $fileName;

                    // correct的重复check：querysame业务type下是否有same的业务标识
                    $existingFiles = $this->defaultFileDomainService->getByOrganizationCodeAndBusinessType($businessType, $organizationCode);
                    $isDuplicate = false;
                    foreach ($existingFiles as $existingFile) {
                        // use userId 字段storage业务标识来判断重复
                        if ($existingFile->getUserId() === $businessIdentifier) {
                            $isDuplicate = true;
                            break;
                        }
                    }

                    if ($isDuplicate) {
                        $this->line("    - 跳过重复file: {$fileName}");
                        ++$skippedFiles;
                        continue;
                    }

                    $this->line("    - handlefile: {$fileName}");

                    try {
                        // readfile内容并转为 base64 format
                        $fileContent = file_get_contents($filePath);
                        $mimeType = mime_content_type($filePath) ?: 'image/png';
                        $base64Content = 'data:' . $mimeType . ';base64,' . base64_encode($fileContent);

                        // 完全参考 ImageWatermarkProcessor 的success做法，但指定file名
                        $uploadFile = new UploadFile($base64Content, 'default-files', $fileName);
                        $this->fileDomainService->uploadByCredential(
                            $organizationCode,
                            $uploadFile,
                            StorageBucketType::Public
                        );

                        // 立即validatefile是否可get（关键validate步骤）
                        $actualKey = $uploadFile->getKey();
                        // 从 key 中提取organization编码，参考 ProviderAppService 的correct做法
                        $keyOrganizationCode = substr($actualKey, 0, strpos($actualKey, '/'));
                        $fileLink = $this->fileDomainService->getLink($keyOrganizationCode, $actualKey, StorageBucketType::Public);
                        if (! $fileLink || ! $fileLink->getUrl()) {
                            throw new Exception('fileuploadfail，无法get访问链接');
                        }

                        // validatesuccess后才createdatabase记录，useactual的upload key
                        $defaultFileEntity = new DefaultFileEntity();
                        $defaultFileEntity->setBusinessType($businessType->value);
                        $defaultFileEntity->setFileType(DefaultFileType::DEFAULT->value);
                        $defaultFileEntity->setKey($actualKey);
                        $defaultFileEntity->setFileSize($fileSize);
                        $defaultFileEntity->setOrganization($organizationCode);
                        $defaultFileEntity->setFileExtension($fileExtension);
                        $defaultFileEntity->setUserId($businessIdentifier); // use业务标识作为 userId

                        // save实体
                        $this->defaultFileDomainService->insert($defaultFileEntity);

                        ++$fileCount;
                    } catch (Exception $e) {
                        $this->error("  - handlefile {$fileName} fail: {$e->getMessage()}");
                        continue; // 不影响后续filehandle
                    }
                }

                $this->line("    - successhandle {$fileCount} 个file");
                $totalFiles += $fileCount;
            } catch (Exception $e) {
                $this->error("  - handle模块 {$moduleName} 时出错: {$e->getMessage()}");
            }
        }

        // 同时handleoriginal的default图标file（如果need的话）
        $this->processDefaultIcons($baseFileDir, $organizationCode, $totalFiles, $skippedFiles);

        $this->info("fileinitializecomplete，共handle {$totalFiles} 个file，跳过 {$skippedFiles} 个已存在的file");
    }

    /**
     * 将模块名映射到对应的业务type.
     */
    protected function mapModuleToBusinessType(string $moduleName): ?DefaultFileBusinessType
    {
        // 尝试直接映射
        try {
            return DefaultFileBusinessType::from($moduleName);
        } catch (ValueError) {
            // 如果直接映射fail，尝试pass名称匹配
            return match (strtolower($moduleName)) {
                'service_provider', 'serviceprovider', 'service-provider' => DefaultFileBusinessType::SERVICE_PROVIDER,
                'flow', 'workflow' => DefaultFileBusinessType::FLOW,
                'delightful', 'default' => DefaultFileBusinessType::Delightful,
                default => null,
            };
        }
    }

    /**
     * handledefault图标file.
     */
    protected function processDefaultIcons(string $baseFileDir, string $organizationCode, int &$totalFiles, int &$skippedFiles): void
    {
        // 如果有need单独handle的default图标，can在这里implement
        // for examplehandle Midjourney 等default图标
    }
}
