<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Interfaces\Provider\Assembler\ProviderConfigAssembler;
use Hyperf\Context\ApplicationContext;
use Hyperf\Coroutine\Parallel;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 清null官方organization的 Delightful service商configuration和model（放indelete软deletedata之front）
        $this->cleanOfficialDelightfulProviderData();

        // cleanup service_provider 相关四张表middle的软deletedata
        $this->cleanSoftDeletedData();

        // 清洗 service_provider 表middle provider_code='Official' 的record
        $this->cleanOfficialProviderData();

        // cleanup service_provider_models 表middle的冗余data
        $this->cleanServiceProviderModelsData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 软deletedata一旦物理deletethen无法restore，所by down method为null
    }

    /**
     * cleanup service_provider 相关表middle的软deletedata.
     */
    private function cleanSoftDeletedData(): void
    {
        $logger = $this->getLogger();
        $logger->info('startcleanup service_provider 相关表的软deletedata');

        try {
            // usetransactionensuredata一致property
            Db::transaction(function () use ($logger) {
                $totalDeleted = 0;

                // 1. delete service_provider 表middle软delete的data
                $deletedCount = Db::table('service_provider')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider 表软deletedata: {$deletedCount} item");

                // 2. delete service_provider_configs 表middle软delete的data
                $deletedCount = Db::table('service_provider_configs')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_configs 表软deletedata: {$deletedCount} item");

                // 3. delete service_provider_models 表middle软delete的data
                $deletedCount = Db::table('service_provider_models')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_models 表软deletedata: {$deletedCount} item");

                // 4. delete service_provider_original_models 表middle软delete的data
                $deletedCount = Db::table('service_provider_original_models')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_original_models 表软deletedata: {$deletedCount} item");

                $logger->info("service_provider 相关表软deletedatacleanupcomplete，总共delete: {$totalDeleted} itemrecord");
            });
        } catch (Throwable $e) {
            $logger->error('cleanup软deletedataproceduremiddlehair生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 清null官方organization的 Delightful service商configuration和model.
     */
    private function cleanOfficialDelightfulProviderData(): void
    {
        $logger = $this->getLogger();
        $logger->info('start清null官方organization的 Delightful service商configuration和model');

        try {
            // usetransactionensuredata一致property
            Db::transaction(function () use ($logger) {
                // get官方organizationencoding
                $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();
                $logger->info("官方organizationencoding: {$officialOrganizationCode}");

                $totalDeleted = 0;

                // 1. find官方organizationmiddle Delightful service商的configurationID
                $delightfulProviderConfigQuery = Db::table('service_provider_configs as configs')
                    ->join('service_provider as providers', 'configs.service_provider_id', '=', 'providers.id')
                    ->select('configs.id')
                    ->where('configs.organization_code', $officialOrganizationCode)
                    ->where('providers.provider_code', 'Official');

                $delightfulProviderConfigs = Db::select($delightfulProviderConfigQuery->toSql(), $delightfulProviderConfigQuery->getBindings());
                $delightfulConfigIds = array_column($delightfulProviderConfigs, 'id');

                if (! empty($delightfulConfigIds)) {
                    $logger->info('找to Delightful service商configurationquantity: ' . count($delightfulConfigIds));

                    // 2. delete官方organizationmiddle Delightful service商down的model
                    $deletedModelsCount = Db::table('service_provider_models')
                        ->where('organization_code', $officialOrganizationCode)
                        ->whereIn('service_provider_config_id', $delightfulConfigIds)
                        ->delete();
                    $totalDeleted += $deletedModelsCount;
                    $logger->info("delete官方organization Delightful service商model: {$deletedModelsCount} item");

                    // 3. delete官方organization的 Delightful service商configuration
                    $deletedConfigsCount = Db::table('service_provider_configs')
                        ->where('organization_code', $officialOrganizationCode)
                        ->whereIn('id', $delightfulConfigIds)
                        ->delete();
                    $totalDeleted += $deletedConfigsCount;
                    $logger->info("delete官方organization Delightful service商configuration: {$deletedConfigsCount} item");
                } else {
                    $logger->info('未找toneedcleanup的 Delightful service商configuration');
                }

                // 4. 额outsidecleanup：delete所have is_office=1 的官方organizationmodel
                $deletedOfficeModelsCount = Db::table('service_provider_models')
                    ->where('organization_code', $officialOrganizationCode)
                    ->where('is_office', 1)
                    ->delete();
                $totalDeleted += $deletedOfficeModelsCount;
                $logger->info("delete官方organization Delightful model(is_office=1): {$deletedOfficeModelsCount} item");

                $logger->info("官方organization Delightful service商datacleanupcomplete，总共delete: {$totalDeleted} itemrecord");
            });
        } catch (Throwable $e) {
            $logger->error('cleanup官方organization Delightful service商dataproceduremiddlehair生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 清洗 Official service商的data.
     */
    private function cleanOfficialProviderData(): void
    {
        $logger = $this->getLogger();
        $logger->info('start清洗 Official service商的 description 和 translate field');

        try {
            // usetransactionensuredata一致property
            Db::transaction(function () use ($logger) {
                // queryneed清洗的 Official service商record
                $query = Db::table('service_provider')
                    ->select(['id', 'description', 'translate'])
                    ->where('provider_code', 'Official');
                $providers = Db::select($query->toSql(), $query->getBindings());

                $updateCount = 0;
                foreach ($providers as $provider) {
                    $needUpdate = false;
                    $updateData = [];

                    // handle description field
                    if (! empty($provider['description']) && strpos($provider['description'], '!') !== false) {
                        $updateData['description'] = str_replace('!', 'I', $provider['description']);
                        $needUpdate = true;
                    }

                    // handle translate field
                    if (! empty($provider['translate']) && strpos($provider['translate'], '!') !== false) {
                        $updateData['translate'] = str_replace('!', 'I', $provider['translate']);
                        $needUpdate = true;
                    }

                    // updaterecord
                    if ($needUpdate) {
                        Db::table('service_provider')
                            ->where('id', $provider['id'])
                            ->update($updateData);
                        ++$updateCount;
                    }
                }

                $logger->info("清洗complete，总共影响line数: {$updateCount}");
                $logger->info('Official service商data清洗complete');
            });
        } catch (Throwable $e) {
            $logger->error('data清洗proceduremiddlehair生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * cleanup service_provider_models 表middle的冗余data.
     */
    private function cleanServiceProviderModelsData(): void
    {
        $logger = $this->getLogger();
        $logger->info('startcleanup service_provider_models 表的冗余data');

        try {
            // 1. get官方organizationencoding
            $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();
            $logger->info("官方organizationencoding: {$officialOrganizationCode}");

            // 2. 单独transaction：reset官方organizationmodel的 model_parent_id
            $this->resetOfficialModelsParentId($officialOrganizationCode, $logger);

            // 3. get官方organization所haveenable的model（notneedtransaction）
            $officialEnabledModels = Db::table('service_provider_models')
                ->where('organization_code', $officialOrganizationCode)
                ->where('status', Status::Enabled->value)
                ->whereNull('deleted_at')
                ->select(['id', 'status'])
                ->get()
                ->keyBy('id')
                ->toArray();

            $officialModelIds = array_keys($officialEnabledModels);
            $logger->info('get官方organizationenablemodelquantity: ' . count($officialModelIds));

            // 4. get所havenon官方organizationencoding（notneedtransaction）
            $allOrganizationCodes = Db::table('service_provider_models')
                ->where('organization_code', '!=', $officialOrganizationCode)
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('organization_code')
                ->toArray();

            $logger->info('needcleanup的organizationquantity: ' . count($allOrganizationCodes));

            // 5. 按organizationhandlecleanupwork（小transaction）
            $this->cleanOrganizationsInBatches($allOrganizationCodes, $officialModelIds, $officialEnabledModels, $logger);
        } catch (Throwable $e) {
            $logger->error('cleanup service_provider_models 冗余dataproceduremiddlehair生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * reset官方organizationmodel的 parent_id（单独transaction）.
     */
    private function resetOfficialModelsParentId(string $officialOrganizationCode, LoggerInterface $logger): void
    {
        Db::transaction(function () use ($officialOrganizationCode, $logger) {
            $updatedCount = Db::table('service_provider_models')
                ->where('organization_code', $officialOrganizationCode)
                ->where('model_parent_id', '!=', 0)
                ->update(['model_parent_id' => 0]);
            $updatedCount && $logger->info("官方organizationmodel model_parent_id reset为 0: {$updatedCount} item");
        });
    }

    /**
     * minute批并haircleanupeachorganization的data（eachorganization独立小transaction）.
     */
    private function cleanOrganizationsInBatches(array $organizationCodes, array $officialModelIds, array $officialEnabledModels, LoggerInterface $logger): void
    {
        $totalDeleted = 0;
        $totalOrgs = count($organizationCodes);

        // 将organizationminute批handle，each批at most 5 organization并hair
        $chunks = array_chunk($organizationCodes, 5);

        foreach ($chunks as $chunkIndex => $chunk) {
            $logger->info('starthandlethe ' . ($chunkIndex + 1) . ' 批organization，quantity: ' . count($chunk));

            $parallel = new Parallel(10);

            // add并hairtask
            foreach ($chunk as $organizationCode) {
                $parallel->add(function () use ($organizationCode, $officialModelIds, $officialEnabledModels) {
                    try {
                        return $this->cleanSingleOrganization($organizationCode, $officialModelIds, $officialEnabledModels);
                    } catch (Throwable $e) {
                        return ['error' => $e, 'org_code' => $organizationCode];
                    }
                }, $organizationCode);
            }

            // execute并hairtask并etc待result
            $results = $parallel->wait();

            // handleresult
            foreach ($results as $orgCode => $result) {
                if (isset($result['error'])) {
                    $logger->error("cleanuporganization {$orgCode} o clockhair生error: " . $result['error']->getMessage());
                } else {
                    $deletedCount = $result['deleted_count'];
                    $totalDeleted += $deletedCount;

                    if ($deletedCount > 0) {
                        $logger->info("organization {$orgCode} delete冗余model: {$deletedCount} item");
                    }
                }
            }

            $logger->info('the ' . ($chunkIndex + 1) . ' 批organizationhandlecomplete');

            // eachhandle一批output进degree
            $processedCount = ($chunkIndex + 1) * 10;
            if ($processedCount > $totalOrgs) {
                $processedCount = $totalOrgs;
            }
            $logger->info("已handle {$processedCount}/{$totalOrgs} organization，累计delete: {$totalDeleted} item");
        }

        $logger->info("service_provider_models 表冗余datacleanupcomplete，总共delete: {$totalDeleted} itemrecord");
    }

    /**
     * cleanup单organization的data（单独transaction）.
     */
    private function cleanSingleOrganization(string $organizationCode, array $officialModelIds, array $officialEnabledModels): array
    {
        return Db::transaction(function () use ($organizationCode, $officialModelIds, $officialEnabledModels) {
            // get官方organizationencodinguseatsecurity防护
            $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();

            // 防护check：ensurenothandle官方organization
            if ($organizationCode === $officialOrganizationCode) {
                return ['deleted_count' => 0];
            }

            $totalDeletedCount = 0;

            // 1. delete所have is_office = 1 的data（防护：non官方organization）
            $isOfficeDeletedCount = Db::table('service_provider_models')
                ->where('organization_code', $organizationCode)
                ->where('organization_code', '!=', $officialOrganizationCode) // 双重防护
                ->where('is_office', 1)
                ->whereNull('deleted_at')
                ->delete();
            $totalDeletedCount += $isOfficeDeletedCount;

            // 2. deletequotenot存inconfiguration的model（批quantityquery和批quantitydelete）
            $invalidConfigDeletedCount = $this->cleanModelsWithInvalidConfig($organizationCode, $officialOrganizationCode);
            $totalDeletedCount += $invalidConfigDeletedCount;

            // 3. deleteconfigurationinvalid的model（configurationdecryptback为nullor所havevalueall是null）
            $invalidConfigDataDeletedCount = $this->cleanModelsWithInvalidConfigData($organizationCode, $officialOrganizationCode);
            $totalDeletedCount += $invalidConfigDataDeletedCount;

            // 4. find model_parent_id not为 0 的data
            $modelsWithParent = Db::table('service_provider_models')
                ->where('organization_code', $organizationCode)
                ->where('model_parent_id', '!=', 0)
                ->where('organization_code', '!=', $officialOrganizationCode) // 防护
                ->whereNull('deleted_at')
                ->select(['id', 'model_parent_id', 'status'])
                ->get();

            $deleteIds = [];

            foreach ($modelsWithParent as $model) {
                $parentId = $model['model_parent_id'];

                // check model_parent_id whetherin官方organization的model id middle
                if (! in_array($parentId, $officialModelIds)) {
                    // model_parent_id in官方organization找notto，markdelete
                    $deleteIds[] = $model['id'];
                    continue;
                }

                // if model_parent_id 存in，butstatus与官方organization一致，alsodelete
                $officialModel = $officialEnabledModels[$parentId] ?? null;
                if ($officialModel && $model['status'] == $officialModel['status']) {
                    $deleteIds[] = $model['id'];
                }
            }

            // 5. 批quantitydelete冗余data（带防护）
            if (! empty($deleteIds)) {
                $redundantDeletedCount = Db::table('service_provider_models')
                    ->whereIn('id', $deleteIds)
                    ->where('organization_code', '!=', $officialOrganizationCode) // 额outside防护
                    ->delete();
                $totalDeletedCount += $redundantDeletedCount;
            }

            return ['deleted_count' => $totalDeletedCount];
        });
    }

    /**
     * cleanupquotenot存inconfiguration的model（批quantityquery和批quantitydelete）.
     */
    private function cleanModelsWithInvalidConfig(string $organizationCode, string $officialOrganizationCode): int
    {
        // 1. 批quantityquery该organizationdown所havemodel的 service_provider_config_id
        $modelConfigs = Db::table('service_provider_models')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // 防护
            ->whereNull('deleted_at')
            ->whereNotNull('service_provider_config_id')
            ->select(['id', 'service_provider_config_id'])
            ->get();

        if ($modelConfigs->isEmpty()) {
            return 0;
        }

        // 2. extract所have唯一的 config_id
        $configIds = $modelConfigs->pluck('service_provider_config_id')->unique()->filter()->toArray();

        if (empty($configIds)) {
            return 0;
        }

        // 3. 批quantityquery存in的 config_id
        $existingConfigIds = Db::table('service_provider_configs')
            ->whereIn('id', $configIds)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        // 4. 找出not存in的 config_id
        $invalidConfigIds = array_diff($configIds, $existingConfigIds);

        if (empty($invalidConfigIds)) {
            return 0;
        }

        // 5. 批quantitydeletequotenot存inconfiguration的model
        return Db::table('service_provider_models')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // 双重防护
            ->whereIn('service_provider_config_id', $invalidConfigIds)
            ->delete();
    }

    /**
     * cleanupconfigurationdatainvalid的model（configurationdecryptback为nullor所havevalueall是null）.
     */
    private function cleanModelsWithInvalidConfigData(string $organizationCode, string $officialOrganizationCode): int
    {
        // 1. query该organizationdown的所haveconfiguration
        $configs = Db::table('service_provider_configs')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // 防护
            ->whereNull('deleted_at')
            ->select(['id', 'config'])
            ->get();

        $invalidConfigIds = [];

        // 2. loopcheckeachconfiguration的validproperty
        foreach ($configs as $config) {
            try {
                // decryptconfiguration（useconfiguration ID 作为 salt）
                $decodedConfig = ProviderConfigAssembler::decodeConfig($config['config'], (string) $config['id']);

                // checkconfigurationwhethervalid
                if ($this->isConfigDataInvalid($decodedConfig)) {
                    $invalidConfigIds[] = $config['id'];
                }
            } catch (Throwable $e) {
                // ifdecryptfail，also认为是invalidconfiguration
                $invalidConfigIds[] = $config['id'];
            }
        }

        if (empty($invalidConfigIds)) {
            return 0;
        }

        // 3. 批quantitydeleteuseinvalidconfiguration的model
        return Db::table('service_provider_models')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // 双重防护
            ->whereIn('service_provider_config_id', $invalidConfigIds)
            ->delete();
    }

    /**
     * checkdecryptback的configurationdatawhetherinvalid.
     * @param mixed $decodedConfig
     */
    private function isConfigDataInvalid($decodedConfig): bool
    {
        // not是array
        if (! is_array($decodedConfig)) {
            return true;
        }

        // array为null
        if (empty($decodedConfig)) {
            return true;
        }

        // checkarraymiddle所havekey的valuewhetherall为null
        foreach ($decodedConfig as $key => $value) {
            // ifhave任何一valuenot为null，thenconfigurationvalid
            if (! empty($value)) {
                return false;
            }
        }

        // 所havevalueall是null，configurationinvalid
        return true;
    }

    /**
     * getlogrecord器.
     */
    private function getLogger(): LoggerInterface
    {
        $container = ApplicationContext::getContainer();
        return $container->get(LoggerFactory::class)?->get('migration');
    }
};
