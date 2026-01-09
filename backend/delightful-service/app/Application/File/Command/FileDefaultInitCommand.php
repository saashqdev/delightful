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

        // getpublichavebucketconfiguration
        $publicBucketConfig = config('cloudfile.storages.' . StorageBucketType::Public->value);
        $this->line('publichavebucketconfiguration:' . json_encode($publicBucketConfig, JSON_UNESCAPED_UNICODE));

        // ifis local driven,notneedinitialize
        if ($publicBucketConfig['adapter'] === 'local') {
            $this->info('thisgrounddriven,notneedinitialize');
            return;
        }

        // executefileinitialize
        $this->initFiles();

        $this->info('filesysteminitializecomplete');
    }

    /**
     * initialize havefile.
     */
    protected function initFiles(): void
    {
        $this->line('startinitializefile...');

        // foundationfiledirectory - usenewpathstructure
        $baseFileDir = BASE_PATH . '/storage/files';
        $defaultModulesDir = $baseFileDir . '/DELIGHTFUL/open/default';

        // checkdefaultmodepiecedirectorywhetherexistsin
        if (! is_dir($defaultModulesDir)) {
            $this->error('defaultmodepiecedirectorynotexistsin: ' . $defaultModulesDir);
            return;
        }

        $totalFiles = 0;
        $skippedFiles = 0;
        $organizationCode = CloudFileRepository::DEFAULT_ICON_ORGANIZATION_CODE;

        // get havemodepiecedirectory
        $moduleDirs = array_filter(glob($defaultModulesDir . '/*'), 'is_dir');

        if (empty($moduleDirs)) {
            $this->warn('nothavefindtoanymodepiecedirectory');
            return;
        }

        $this->line('handlemodepiecefile:');

        // traverseeachmodepiecedirectory
        foreach ($moduleDirs as $moduleDir) {
            $moduleName = basename($moduleDir);

            try {
                // trywillmodepiecenamemappingtotoshouldbusinesstype
                $businessType = $this->mapModuleToBusinessType($moduleName);

                if ($businessType === null) {
                    $this->warn("  - skipunknownmodepiece: {$moduleName}");
                    continue;
                }

                $this->line("  - handlemodepiece: {$moduleName} (businesstype: {$businessType->value})");

                // getthemodepiecedirectorydown havefile
                $files = array_filter(glob($moduleDir . '/*'), 'is_file');

                if (empty($files)) {
                    $this->line('    - nothavefindtoanyfile');
                    continue;
                }

                $fileCount = 0;

                // handleeachfile
                foreach ($files as $filePath) {
                    $fileName = basename($filePath);
                    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $fileSize = filesize($filePath);

                    // generatebusinessuniqueoneidentifier(useatduplicatecheck)
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
                        // readfilecontentandtransferfor base64 format
                        $fileContent = file_get_contents($filePath);
                        $mimeType = mime_content_type($filePath) ?: 'image/png';
                        $base64Content = 'data:' . $mimeType . ';base64,' . base64_encode($fileContent);

                        // completeallreference ImageWatermarkProcessor successpractice,butfingersetfilename
                        $uploadFile = new UploadFile($base64Content, 'default-files', $fileName);
                        $this->fileDomainService->uploadByCredential(
                            $organizationCode,
                            $uploadFile,
                            StorageBucketType::Public
                        );

                        // immediatelyvalidatefilewhethercanget(closekeyvalidatestep)
                        $actualKey = $uploadFile->getKey();
                        // from key middleextractorganizationencoding,reference ProviderAppService correctpractice
                        $keyOrganizationCode = substr($actualKey, 0, strpos($actualKey, '/'));
                        $fileLink = $this->fileDomainService->getLink($keyOrganizationCode, $actualKey, StorageBucketType::Public);
                        if (! $fileLink || ! $fileLink->getUrl()) {
                            throw new Exception('fileuploadfail,nomethodgetaccesslink');
                        }

                        // validatesuccessbackonlycreatedatabaserecord,useactualupload key
                        $defaultFileEntity = new DefaultFileEntity();
                        $defaultFileEntity->setBusinessType($businessType->value);
                        $defaultFileEntity->setFileType(DefaultFileType::DEFAULT->value);
                        $defaultFileEntity->setKey($actualKey);
                        $defaultFileEntity->setFileSize($fileSize);
                        $defaultFileEntity->setOrganization($organizationCode);
                        $defaultFileEntity->setFileExtension($fileExtension);
                        $defaultFileEntity->setUserId($businessIdentifier); // usebusinessidentifierasfor userId

                        // saveactualbody
                        $this->defaultFileDomainService->insert($defaultFileEntity);

                        ++$fileCount;
                    } catch (Exception $e) {
                        $this->error("  - handlefile {$fileName} fail: {$e->getMessage()}");
                        continue; // notimpactbackcontinuefilehandle
                    }
                }

                $this->line("    - successhandle {$fileCount} file");
                $totalFiles += $fileCount;
            } catch (Exception $e) {
                $this->error("  - handlemodepiece {$moduleName} o clockout错: {$e->getMessage()}");
            }
        }

        // meanwhilehandleoriginaldefaultgraphmarkfile(ifneed话)
        $this->processDefaultIcons($baseFileDir, $organizationCode, $totalFiles, $skippedFiles);

        $this->info("fileinitializecomplete,共handle {$totalFiles} file,skip {$skippedFiles} alreadyexistsinfile");
    }

    /**
     * willmodepiecenamemappingtotoshouldbusinesstype.
     */
    protected function mapModuleToBusinessType(string $moduleName): ?DefaultFileBusinessType
    {
        // trydirectlymapping
        try {
            return DefaultFileBusinessType::from($moduleName);
        } catch (ValueError) {
            // ifdirectlymappingfail,trypassnamematch
            return match (strtolower($moduleName)) {
                'service_provider', 'serviceprovider', 'service-provider' => DefaultFileBusinessType::SERVICE_PROVIDER,
                'flow', 'workflow' => DefaultFileBusinessType::FLOW,
                'delightful', 'default' => DefaultFileBusinessType::Delightful,
                default => null,
            };
        }
    }

    /**
     * handledefaultgraphmarkfile.
     */
    protected function processDefaultIcons(string $baseFileDir, string $organizationCode, int &$totalFiles, int &$skippedFiles): void
    {
        // ifhaveneedsingleuniquehandledefaultgraphmark,caninthiswithinimplement
        // for examplehandle Midjourney etcdefaultgraphmark
    }
}
