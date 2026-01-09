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
        $this->line('公have桶configuration:' . json_encode($publicBucketConfig, JSON_UNESCAPED_UNICODE));

        // ifis local 驱动,notneedinitialize
        if ($publicBucketConfig['adapter'] === 'local') {
            $this->info('本ground驱动,notneedinitialize');
            return;
        }

        // executefileinitialize
        $this->initFiles();

        $this->info('filesysteminitializecomplete');
    }

    /**
     * initialize所havefile.
     */
    protected function initFiles(): void
    {
        $this->line('startinitializefile...');

        // 基础filedirectory - usenewpathstructure
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
            $this->warn('nothave找toany模piecedirectory');
            return;
        }

        $this->line('handle模piecefile:');

        // 遍历each模piecedirectory
        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);

            try {
                // 尝试will模piece名mappingtoto应businesstype
                $businessType = $this->mapModuleToBusinessType($moduleName);

                if ($businessType === null) {
                    $this->warn("  - skipunknown模piece: {$moduleName}");
                    continue;
                }

                $this->line("  - handle模piece: {$moduleName} (businesstype: {$businessType->value})");

                // getthe模piecedirectorydown所havefile
                $files = array_filter(glob($moduleDir . '/*'), 'is_file');

                if (empty($files)) {
                    $this->line('    - nothave找toanyfile');
                    continue;
                }

                $fileCount = 0;

                // handleeachfile
                foreach ($files as $filePath) {
                    $fileName = basename($filePath);
                    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $fileSize = filesize($filePath);

                    // generatebusiness唯oneidentifier(useatduplicatecheck)
                    $businessIdentifier = $moduleName . '/' . $fileName;

                    // correctduplicatecheck:querysamebusinesstypedownwhetherhavesamebusinessidentifier
                    $existingFiles = $this->defaultFileDomainService->getByOrganizationCodeAndBusinessType($businessType, $organizationCode);
                    $isDuplicate = false;
                    foreach ($existingFiles as $existingFile) {
                        // use userId fieldstoragebusinessidentifiercomejudgeduplicate
                        if ($existingFile->getUserId() === $businessIdentifier) {
                            $isDuplicate = true;
                            break;
                        }
                    }

                    if ($isDuplicate) {
                        $this->line("    - skipduplicatefile: {$fileName}");
                        ++$skippedFiles;
                        continue;
                    }

                    $this->line("    - handlefile: {$fileName}");

                    try {
                        // readfilecontentand转for base64 format
                        $fileContent = file_get_contents($filePath);
                        $mimeType = mime_content_type($filePath) ?: 'image/png';
                        $base64Content = 'data:' . $mimeType . ';base64,' . base64_encode($fileContent);

                        // 完all参考 ImageWatermarkProcessor success做法,butfinger定file名
                        $uploadFile = new UploadFile($base64Content, 'default-files', $fileName);
                        $this->fileDomainService->uploadByCredential(
                            $organizationCode,
                            $uploadFile,
                            StorageBucketType::Public
                        );

                        // immediatelyvalidatefilewhethercanget(closekeyvalidatestep)
                        $actualKey = $uploadFile->getKey();
                        // from key middleextractorganizationencoding,参考 ProviderAppService correct做法
                        $keyOrganizationCode = substr($actualKey, 0, strpos($actualKey, '/'));
                        $fileLink = $this->fileDomainService->getLink($keyOrganizationCode, $actualKey, StorageBucketType::Public);
                        if (! $fileLink || ! $fileLink->getUrl()) {
                            throw new Exception('fileuploadfail,no法getaccesslink');
                        }

                        // validatesuccessback才createdatabaserecord,useactualupload key
                        $defaultFileEntity = new DefaultFileEntity();
                        $defaultFileEntity->setBusinessType($businessType->value);
                        $defaultFileEntity->setFileType(DefaultFileType::DEFAULT->value);
                        $defaultFileEntity->setKey($actualKey);
                        $defaultFileEntity->setFileSize($fileSize);
                        $defaultFileEntity->setOrganization($organizationCode);
                        $defaultFileEntity->setFileExtension($fileExtension);
                        $defaultFileEntity->setUserId($businessIdentifier); // usebusinessidentifierasfor userId

                        // save实body
                        $this->defaultFileDomainService->insert($defaultFileEntity);

                        ++$fileCount;
                    } catch (Exception $e) {
                        $this->error("  - handlefile {$fileName} fail: {$e->getMessage()}");
                        continue; // notimpactback续filehandle
                    }
                }

                $this->line("    - successhandle {$fileCount} file");
                $totalFiles += $fileCount;
            } catch (Exception $e) {
                $this->error("  - handle模piece {$moduleName} o clockout错: {$e->getMessage()}");
            }
        }

        // meanwhilehandleoriginaldefaultgraph标file(ifneed话)
        $this->processDefaultIcons($baseFileDir, $organizationCode, $totalFiles, $skippedFiles);

        $this->info("fileinitializecomplete,共handle {$totalFiles} file,skip {$skippedFiles} already存infile");
    }

    /**
     * will模piece名mappingtoto应businesstype.
     */
    protected function mapModuleToBusinessType(string $moduleName): ?DefaultFileBusinessType
    {
        // 尝试直接mapping
        try {
            return DefaultFileBusinessType::from($moduleName);
        } catch (ValueError) {
            // if直接mappingfail,尝试passnamematch
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
        // ifhaveneedsingle独handledefaultgraph标,caninthiswithinimplement
        // for examplehandle Midjourney etcdefaultgraph标
    }
}
