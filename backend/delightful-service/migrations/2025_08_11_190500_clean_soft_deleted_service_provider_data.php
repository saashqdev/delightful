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
        // 清null官方organization的 Delightful 服务商configuration和model（放在delete软deletedata之前）
        $this->cleanOfficialDelightfulProviderData();

        // 清理 service_provider 相关四张表中的软deletedata
        $this->cleanSoftDeletedData();

        // 清洗 service_provider 表中 provider_code='Official' 的record
        $this->cleanOfficialProviderData();

        // 清理 service_provider_models 表中的冗余data
        $this->cleanServiceProviderModelsData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 软deletedata一旦物理delete就无法restore，所以 down method为null
    }

    /**
     * 清理 service_provider 相关表中的软deletedata.
     */
    private function cleanSoftDeletedData(): void
    {
        $logger = $this->getLogger();
        $logger->info('start清理 service_provider 相关表的软deletedata');

        try {
            // usetransactionensuredata一致性
            Db::transaction(function () use ($logger) {
                $totalDeleted = 0;

                // 1. delete service_provider 表中软delete的data
                $deletedCount = Db::table('service_provider')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider 表软deletedata: {$deletedCount} 条");

                // 2. delete service_provider_configs 表中软delete的data
                $deletedCount = Db::table('service_provider_configs')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_configs 表软deletedata: {$deletedCount} 条");

                // 3. delete service_provider_models 表中软delete的data
                $deletedCount = Db::table('service_provider_models')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_models 表软deletedata: {$deletedCount} 条");

                // 4. delete service_provider_original_models 表中软delete的data
                $deletedCount = Db::table('service_provider_original_models')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_original_models 表软deletedata: {$deletedCount} 条");

                $logger->info("service_provider 相关表软deletedata清理complete，总共delete: {$totalDeleted} 条record");
            });
        } catch (Throwable $e) {
            $logger->error('清理软deletedata过程中发生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 清null官方organization的 Delightful 服务商configuration和model.
     */
    private function cleanOfficialDelightfulProviderData(): void
    {
        $logger = $this->getLogger();
        $logger->info('start清null官方organization的 Delightful 服务商configuration和model');

        try {
            // usetransactionensuredata一致性
            Db::transaction(function () use ($logger) {
                // get官方organizationencoding
                $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();
                $logger->info("官方organizationencoding: {$officialOrganizationCode}");

                $totalDeleted = 0;

                // 1. 查找官方organization中 Delightful 服务商的configurationID
                $delightfulProviderConfigQuery = Db::table('service_provider_configs as configs')
                    ->join('service_provider as providers', 'configs.service_provider_id', '=', 'providers.id')
                    ->select('configs.id')
                    ->where('configs.organization_code', $officialOrganizationCode)
                    ->where('providers.provider_code', 'Official');

                $delightfulProviderConfigs = Db::select($delightfulProviderConfigQuery->toSql(), $delightfulProviderConfigQuery->getBindings());
                $delightfulConfigIds = array_column($delightfulProviderConfigs, 'id');

                if (! empty($delightfulConfigIds)) {
                    $logger->info('找到 Delightful 服务商configurationquantity: ' . count($delightfulConfigIds));

                    // 2. delete官方organization中 Delightful 服务商下的model
                    $deletedModelsCount = Db::table('service_provider_models')
                        ->where('organization_code', $officialOrganizationCode)
                        ->whereIn('service_provider_config_id', $delightfulConfigIds)
                        ->delete();
                    $totalDeleted += $deletedModelsCount;
                    $logger->info("delete官方organization Delightful 服务商model: {$deletedModelsCount} 条");

                    // 3. delete官方organization的 Delightful 服务商configuration
                    $deletedConfigsCount = Db::table('service_provider_configs')
                        ->where('organization_code', $officialOrganizationCode)
                        ->whereIn('id', $delightfulConfigIds)
                        ->delete();
                    $totalDeleted += $deletedConfigsCount;
                    $logger->info("delete官方organization Delightful 服务商configuration: {$deletedConfigsCount} 条");
                } else {
                    $logger->info('未找到need清理的 Delightful 服务商configuration');
                }

                // 4. 额外清理：delete所有 is_office=1 的官方organizationmodel
                $deletedOfficeModelsCount = Db::table('service_provider_models')
                    ->where('organization_code', $officialOrganizationCode)
                    ->where('is_office', 1)
                    ->delete();
                $totalDeleted += $deletedOfficeModelsCount;
                $logger->info("delete官方organization Delightful model(is_office=1): {$deletedOfficeModelsCount} 条");

                $logger->info("官方organization Delightful 服务商data清理complete，总共delete: {$totalDeleted} 条record");
            });
        } catch (Throwable $e) {
            $logger->error('清理官方organization Delightful 服务商data过程中发生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 清洗 Official 服务商的data.
     */
    private function cleanOfficialProviderData(): void
    {
        $logger = $this->getLogger();
        $logger->info('start清洗 Official 服务商的 description 和 translate 字段');

        try {
            // usetransactionensuredata一致性
            Db::transaction(function () use ($logger) {
                // queryneed清洗的 Official 服务商record
                $query = Db::table('service_provider')
                    ->select(['id', 'description', 'translate'])
                    ->where('provider_code', 'Official');
                $providers = Db::select($query->toSql(), $query->getBindings());

                $updateCount = 0;
                foreach ($providers as $provider) {
                    $needUpdate = false;
                    $updateData = [];

                    // handle description 字段
                    if (! empty($provider['description']) && strpos($provider['description'], '!') !== false) {
                        $updateData['description'] = str_replace('!', 'I', $provider['description']);
                        $needUpdate = true;
                    }

                    // handle translate 字段
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

                $logger->info("清洗complete，总共影响行数: {$updateCount}");
                $logger->info('Official 服务商data清洗complete');
            });
        } catch (Throwable $e) {
            $logger->error('data清洗过程中发生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 清理 service_provider_models 表中的冗余data.
     */
    private function cleanServiceProviderModelsData(): void
    {
        $logger = $this->getLogger();
        $logger->info('start清理 service_provider_models 表的冗余data');

        try {
            // 1. get官方organizationencoding
            $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();
            $logger->info("官方organizationencoding: {$officialOrganizationCode}");

            // 2. 单独transaction：reset官方organizationmodel的 model_parent_id
            $this->resetOfficialModelsParentId($officialOrganizationCode, $logger);

            // 3. get官方organization所有启用的model（不needtransaction）
            $officialEnabledModels = Db::table('service_provider_models')
                ->where('organization_code', $officialOrganizationCode)
                ->where('status', Status::Enabled->value)
                ->whereNull('deleted_at')
                ->select(['id', 'status'])
                ->get()
                ->keyBy('id')
                ->toArray();

            $officialModelIds = array_keys($officialEnabledModels);
            $logger->info('get官方organization启用modelquantity: ' . count($officialModelIds));

            // 4. get所有非官方organizationencoding（不needtransaction）
            $allOrganizationCodes = Db::table('service_provider_models')
                ->where('organization_code', '!=', $officialOrganizationCode)
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('organization_code')
                ->toArray();

            $logger->info('need清理的organizationquantity: ' . count($allOrganizationCodes));

            // 5. 按organizationhandle清理工作（小transaction）
            $this->cleanOrganizationsInBatches($allOrganizationCodes, $officialModelIds, $officialEnabledModels, $logger);
        } catch (Throwable $e) {
            $logger->error('清理 service_provider_models 冗余data过程中发生error: ' . $e->getMessage());
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
            $updatedCount && $logger->info("官方organizationmodel model_parent_id reset为 0: {$updatedCount} 条");
        });
    }

    /**
     * 分批并发清理各个organization的data（每个organization独立小transaction）.
     */
    private function cleanOrganizationsInBatches(array $organizationCodes, array $officialModelIds, array $officialEnabledModels, LoggerInterface $logger): void
    {
        $totalDeleted = 0;
        $totalOrgs = count($organizationCodes);

        // 将organization分批handle，每批at most 5 个organization并发
        $chunks = array_chunk($organizationCodes, 5);

        foreach ($chunks as $chunkIndex => $chunk) {
            $logger->info('starthandle第 ' . ($chunkIndex + 1) . ' 批organization，quantity: ' . count($chunk));

            $parallel = new Parallel(10);

            // 添加并发task
            foreach ($chunk as $organizationCode) {
                $parallel->add(function () use ($organizationCode, $officialModelIds, $officialEnabledModels) {
                    try {
                        return $this->cleanSingleOrganization($organizationCode, $officialModelIds, $officialEnabledModels);
                    } catch (Throwable $e) {
                        return ['error' => $e, 'org_code' => $organizationCode];
                    }
                }, $organizationCode);
            }

            // execute并发task并等待result
            $results = $parallel->wait();

            // handleresult
            foreach ($results as $orgCode => $result) {
                if (isset($result['error'])) {
                    $logger->error("清理organization {$orgCode} 时发生error: " . $result['error']->getMessage());
                } else {
                    $deletedCount = $result['deleted_count'];
                    $totalDeleted += $deletedCount;

                    if ($deletedCount > 0) {
                        $logger->info("organization {$orgCode} delete冗余model: {$deletedCount} 条");
                    }
                }
            }

            $logger->info('第 ' . ($chunkIndex + 1) . ' 批organizationhandlecomplete');

            // 每handle一批output进度
            $processedCount = ($chunkIndex + 1) * 10;
            if ($processedCount > $totalOrgs) {
                $processedCount = $totalOrgs;
            }
            $logger->info("已handle {$processedCount}/{$totalOrgs} 个organization，累计delete: {$totalDeleted} 条");
        }

        $logger->info("service_provider_models 表冗余data清理complete，总共delete: {$totalDeleted} 条record");
    }

    /**
     * 清理单个organization的data（单独transaction）.
     */
    private function cleanSingleOrganization(string $organizationCode, array $officialModelIds, array $officialEnabledModels): array
    {
        return Db::transaction(function () use ($organizationCode, $officialModelIds, $officialEnabledModels) {
            // get官方organizationencoding用于安全防护
            $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();

            // 防护check：ensure不handle官方organization
            if ($organizationCode === $officialOrganizationCode) {
                return ['deleted_count' => 0];
            }

            $totalDeletedCount = 0;

            // 1. delete所有 is_office = 1 的data（防护：非官方organization）
            $isOfficeDeletedCount = Db::table('service_provider_models')
                ->where('organization_code', $organizationCode)
                ->where('organization_code', '!=', $officialOrganizationCode) // 双重防护
                ->where('is_office', 1)
                ->whereNull('deleted_at')
                ->delete();
            $totalDeletedCount += $isOfficeDeletedCount;

            // 2. deletequote不存在configuration的model（批量query和批量delete）
            $invalidConfigDeletedCount = $this->cleanModelsWithInvalidConfig($organizationCode, $officialOrganizationCode);
            $totalDeletedCount += $invalidConfigDeletedCount;

            // 3. deleteconfigurationinvalid的model（configurationdecrypt后为null或所有value都是null）
            $invalidConfigDataDeletedCount = $this->cleanModelsWithInvalidConfigData($organizationCode, $officialOrganizationCode);
            $totalDeletedCount += $invalidConfigDataDeletedCount;

            // 4. 查找 model_parent_id 不为 0 的data
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

                // check model_parent_id 是否在官方organization的model id 中
                if (! in_array($parentId, $officialModelIds)) {
                    // model_parent_id 在官方organization找不到，markdelete
                    $deleteIds[] = $model['id'];
                    continue;
                }

                // 如果 model_parent_id 存在，但status与官方organization一致，也delete
                $officialModel = $officialEnabledModels[$parentId] ?? null;
                if ($officialModel && $model['status'] == $officialModel['status']) {
                    $deleteIds[] = $model['id'];
                }
            }

            // 5. 批量delete冗余data（带防护）
            if (! empty($deleteIds)) {
                $redundantDeletedCount = Db::table('service_provider_models')
                    ->whereIn('id', $deleteIds)
                    ->where('organization_code', '!=', $officialOrganizationCode) // 额外防护
                    ->delete();
                $totalDeletedCount += $redundantDeletedCount;
            }

            return ['deleted_count' => $totalDeletedCount];
        });
    }

    /**
     * 清理quote不存在configuration的model（批量query和批量delete）.
     */
    private function cleanModelsWithInvalidConfig(string $organizationCode, string $officialOrganizationCode): int
    {
        // 1. 批量query该organization下所有model的 service_provider_config_id
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

        // 2. 提取所有唯一的 config_id
        $configIds = $modelConfigs->pluck('service_provider_config_id')->unique()->filter()->toArray();

        if (empty($configIds)) {
            return 0;
        }

        // 3. 批量query存在的 config_id
        $existingConfigIds = Db::table('service_provider_configs')
            ->whereIn('id', $configIds)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        // 4. 找出不存在的 config_id
        $invalidConfigIds = array_diff($configIds, $existingConfigIds);

        if (empty($invalidConfigIds)) {
            return 0;
        }

        // 5. 批量deletequote不存在configuration的model
        return Db::table('service_provider_models')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // 双重防护
            ->whereIn('service_provider_config_id', $invalidConfigIds)
            ->delete();
    }

    /**
     * 清理configurationdatainvalid的model（configurationdecrypt后为null或所有value都是null）.
     */
    private function cleanModelsWithInvalidConfigData(string $organizationCode, string $officialOrganizationCode): int
    {
        // 1. query该organization下的所有configuration
        $configs = Db::table('service_provider_configs')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // 防护
            ->whereNull('deleted_at')
            ->select(['id', 'config'])
            ->get();

        $invalidConfigIds = [];

        // 2. 循环check每个configuration的valid性
        foreach ($configs as $config) {
            try {
                // decryptconfiguration（useconfiguration ID 作为 salt）
                $decodedConfig = ProviderConfigAssembler::decodeConfig($config['config'], (string) $config['id']);

                // checkconfiguration是否valid
                if ($this->isConfigDataInvalid($decodedConfig)) {
                    $invalidConfigIds[] = $config['id'];
                }
            } catch (Throwable $e) {
                // 如果decryptfail，也认为是invalidconfiguration
                $invalidConfigIds[] = $config['id'];
            }
        }

        if (empty($invalidConfigIds)) {
            return 0;
        }

        // 3. 批量deleteuseinvalidconfiguration的model
        return Db::table('service_provider_models')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // 双重防护
            ->whereIn('service_provider_config_id', $invalidConfigIds)
            ->delete();
    }

    /**
     * checkdecrypt后的configurationdata是否invalid.
     * @param mixed $decodedConfig
     */
    private function isConfigDataInvalid($decodedConfig): bool
    {
        // 不是array
        if (! is_array($decodedConfig)) {
            return true;
        }

        // array为null
        if (empty($decodedConfig)) {
            return true;
        }

        // checkarray中所有key的value是否都为null
        foreach ($decodedConfig as $key => $value) {
            // 如果有任何一个value不为null，则configurationvalid
            if (! empty($value)) {
                return false;
            }
        }

        // 所有value都是null，configurationinvalid
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
