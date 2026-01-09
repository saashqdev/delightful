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
        $this->setDescription('initialize默认文件');
    }

    public function handle(): void
    {
        $this->fileDomainService = $this->container->get(FileDomainService::class);
        $this->defaultFileDomainService = $this->container->get(DefaultFileDomainService::class);

        // 获取公有桶配置
        $publicBucketConfig = config('cloudfile.storages.' . StorageBucketType::Public->value);
        $this->line('公有桶配置：' . json_encode($publicBucketConfig, JSON_UNESCAPED_UNICODE));

        // 如果是 local 驱动，不需要initialize
        if ($publicBucketConfig['adapter'] === 'local') {
            $this->info('本地驱动，不需要initialize');
            return;
        }

        // execute文件initialize
        $this->initFiles();

        $this->info('文件系统initializecomplete');
    }

    /**
     * initialize所有文件.
     */
    protected function initFiles(): void
    {
        $this->line('startinitialize文件...');

        // 基础文件目录 - use新的路径结构
        $baseFileDir = BASE_PATH . '/storage/files';
        $defaultModulesDir = $baseFileDir . '/DELIGHTFUL/open/default';

        // 检查默认模块目录是否存在
        if (! is_dir($defaultModulesDir)) {
            $this->error('默认模块目录不存在: ' . $defaultModulesDir);
            return;
        }

        $totalFiles = 0;
        $skippedFiles = 0;
        $organizationCode = CloudFileRepository::DEFAULT_ICON_ORGANIZATION_CODE;

        // 获取所有模块目录
        $moduleDirs = array_filter(glob($defaultModulesDir . '/*'), 'is_dir');

        if (empty($moduleDirs)) {
            $this->warn('没有找到任何模块目录');
            return;
        }

        $this->line('handle模块文件:');

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

                // 获取该模块目录下的所有文件
                $files = array_filter(glob($moduleDir . '/*'), 'is_file');

                if (empty($files)) {
                    $this->line('    - 没有找到任何文件');
                    continue;
                }

                $fileCount = 0;

                // handle每个文件
                foreach ($files as $filePath) {
                    $fileName = basename($filePath);
                    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $fileSize = filesize($filePath);

                    // generate业务唯一标识（用于重复检查）
                    $businessIdentifier = $moduleName . '/' . $fileName;

                    // 正确的重复检查：query相同业务type下是否有相同的业务标识
                    $existingFiles = $this->defaultFileDomainService->getByOrganizationCodeAndBusinessType($businessType, $organizationCode);
                    $isDuplicate = false;
                    foreach ($existingFiles as $existingFile) {
                        // use userId 字段存储业务标识来判断重复
                        if ($existingFile->getUserId() === $businessIdentifier) {
                            $isDuplicate = true;
                            break;
                        }
                    }

                    if ($isDuplicate) {
                        $this->line("    - 跳过重复文件: {$fileName}");
                        ++$skippedFiles;
                        continue;
                    }

                    $this->line("    - handle文件: {$fileName}");

                    try {
                        // 读取文件内容并转为 base64 格式
                        $fileContent = file_get_contents($filePath);
                        $mimeType = mime_content_type($filePath) ?: 'image/png';
                        $base64Content = 'data:' . $mimeType . ';base64,' . base64_encode($fileContent);

                        // 完全参考 ImageWatermarkProcessor 的success做法，但指定文件名
                        $uploadFile = new UploadFile($base64Content, 'default-files', $fileName);
                        $this->fileDomainService->uploadByCredential(
                            $organizationCode,
                            $uploadFile,
                            StorageBucketType::Public
                        );

                        // 立即validate文件是否可获取（关键validate步骤）
                        $actualKey = $uploadFile->getKey();
                        // 从 key 中提取organization编码，参考 ProviderAppService 的正确做法
                        $keyOrganizationCode = substr($actualKey, 0, strpos($actualKey, '/'));
                        $fileLink = $this->fileDomainService->getLink($keyOrganizationCode, $actualKey, StorageBucketType::Public);
                        if (! $fileLink || ! $fileLink->getUrl()) {
                            throw new Exception('文件上传fail，无法获取访问链接');
                        }

                        // validatesuccess后才create数据库记录，use实际的上传 key
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
                        $this->error("  - handle文件 {$fileName} fail: {$e->getMessage()}");
                        continue; // 不影响后续文件handle
                    }
                }

                $this->line("    - successhandle {$fileCount} 个文件");
                $totalFiles += $fileCount;
            } catch (Exception $e) {
                $this->error("  - handle模块 {$moduleName} 时出错: {$e->getMessage()}");
            }
        }

        // 同时handle原始的默认图标文件（如果需要的话）
        $this->processDefaultIcons($baseFileDir, $organizationCode, $totalFiles, $skippedFiles);

        $this->info("文件initializecomplete，共handle {$totalFiles} 个文件，跳过 {$skippedFiles} 个已存在的文件");
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
            // 如果直接映射fail，尝试通过名称匹配
            return match (strtolower($moduleName)) {
                'service_provider', 'serviceprovider', 'service-provider' => DefaultFileBusinessType::SERVICE_PROVIDER,
                'flow', 'workflow' => DefaultFileBusinessType::FLOW,
                'delightful', 'default' => DefaultFileBusinessType::Delightful,
                default => null,
            };
        }
    }

    /**
     * handle默认图标文件.
     */
    protected function processDefaultIcons(string $baseFileDir, string $organizationCode, int &$totalFiles, int &$skippedFiles): void
    {
        // 如果有需要单独handle的默认图标，可以在这里implement
        // for examplehandle Midjourney 等默认图标
    }
}
