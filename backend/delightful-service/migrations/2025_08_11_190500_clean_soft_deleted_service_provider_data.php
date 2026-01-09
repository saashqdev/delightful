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
        // 清null官方organization的 Delightful 服务商configuration和模型（放在delete软delete数据之前）
        $this->cleanOfficialDelightfulProviderData();

        // 清理 service_provider 相关四张表中的软delete数据
        $this->cleanSoftDeletedData();

        // 清洗 service_provider 表中 provider_code='Official' 的记录
        $this->cleanOfficialProviderData();

        // 清理 service_provider_models 表中的冗余数据
        $this->cleanServiceProviderModelsData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 软delete数据一旦物理delete就无法restore，所以 down method为null
    }

    /**
     * 清理 service_provider 相关表中的软delete数据.
     */
    private function cleanSoftDeletedData(): void
    {
        $logger = $this->getLogger();
        $logger->info('start清理 service_provider 相关表的软delete数据');

        try {
            // use事务确保数据一致性
            Db::transaction(function () use ($logger) {
                $totalDeleted = 0;

                // 1. delete service_provider 表中软delete的数据
                $deletedCount = Db::table('service_provider')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider 表软delete数据: {$deletedCount} 条");

                // 2. delete service_provider_configs 表中软delete的数据
                $deletedCount = Db::table('service_provider_configs')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_configs 表软delete数据: {$deletedCount} 条");

                // 3. delete service_provider_models 表中软delete的数据
                $deletedCount = Db::table('service_provider_models')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_models 表软delete数据: {$deletedCount} 条");

                // 4. delete service_provider_original_models 表中软delete的数据
                $deletedCount = Db::table('service_provider_original_models')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_original_models 表软delete数据: {$deletedCount} 条");

                $logger->info("service_provider 相关表软delete数据清理complete，总共delete: {$totalDeleted} 条记录");
            });
        } catch (Throwable $e) {
            $logger->error('清理软delete数据过程中发生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 清null官方organization的 Delightful 服务商configuration和模型.
     */
    private function cleanOfficialDelightfulProviderData(): void
    {
        $logger = $this->getLogger();
        $logger->info('start清null官方organization的 Delightful 服务商configuration和模型');

        try {
            // use事务确保数据一致性
            Db::transaction(function () use ($logger) {
                // 获取官方organization编码
                $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();
                $logger->info("官方organization编码: {$officialOrganizationCode}");

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
                    $logger->info('找到 Delightful 服务商configuration数量: ' . count($delightfulConfigIds));

                    // 2. delete官方organization中 Delightful 服务商下的模型
                    $deletedModelsCount = Db::table('service_provider_models')
                        ->where('organization_code', $officialOrganizationCode)
                        ->whereIn('service_provider_config_id', $delightfulConfigIds)
                        ->delete();
                    $totalDeleted += $deletedModelsCount;
                    $logger->info("delete官方organization Delightful 服务商模型: {$deletedModelsCount} 条");

                    // 3. delete官方organization的 Delightful 服务商configuration
                    $deletedConfigsCount = Db::table('service_provider_configs')
                        ->where('organization_code', $officialOrganizationCode)
                        ->whereIn('id', $delightfulConfigIds)
                        ->delete();
                    $totalDeleted += $deletedConfigsCount;
                    $logger->info("delete官方organization Delightful 服务商configuration: {$deletedConfigsCount} 条");
                } else {
                    $logger->info('未找到需要清理的 Delightful 服务商configuration');
                }

                // 4. 额外清理：delete所有 is_office=1 的官方organization模型
                $deletedOfficeModelsCount = Db::table('service_provider_models')
                    ->where('organization_code', $officialOrganizationCode)
                    ->where('is_office', 1)
                    ->delete();
                $totalDeleted += $deletedOfficeModelsCount;
                $logger->info("delete官方organization Delightful 模型(is_office=1): {$deletedOfficeModelsCount} 条");

                $logger->info("官方organization Delightful 服务商数据清理complete，总共delete: {$totalDeleted} 条记录");
            });
        } catch (Throwable $e) {
            $logger->error('清理官方organization Delightful 服务商数据过程中发生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 清洗 Official 服务商的数据.
     */
    private function cleanOfficialProviderData(): void
    {
        $logger = $this->getLogger();
        $logger->info('start清洗 Official 服务商的 description 和 translate 字段');

        try {
            // use事务确保数据一致性
            Db::transaction(function () use ($logger) {
                // query需要清洗的 Official 服务商记录
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

                    // 更新记录
                    if ($needUpdate) {
                        Db::table('service_provider')
                            ->where('id', $provider['id'])
                            ->update($updateData);
                        ++$updateCount;
                    }
                }

                $logger->info("清洗complete，总共影响行数: {$updateCount}");
                $logger->info('Official 服务商数据清洗complete');
            });
        } catch (Throwable $e) {
            $logger->error('数据清洗过程中发生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 清理 service_provider_models 表中的冗余数据.
     */
    private function cleanServiceProviderModelsData(): void
    {
        $logger = $this->getLogger();
        $logger->info('start清理 service_provider_models 表的冗余数据');

        try {
            // 1. 获取官方organization编码
            $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();
            $logger->info("官方organization编码: {$officialOrganizationCode}");

            // 2. 单独事务：reset官方organization模型的 model_parent_id
            $this->resetOfficialModelsParentId($officialOrganizationCode, $logger);

            // 3. 获取官方organization所有启用的模型（不需要事务）
            $officialEnabledModels = Db::table('service_provider_models')
                ->where('organization_code', $officialOrganizationCode)
                ->where('status', Status::Enabled->value)
                ->whereNull('deleted_at')
                ->select(['id', 'status'])
                ->get()
                ->keyBy('id')
                ->toArray();

            $officialModelIds = array_keys($officialEnabledModels);
            $logger->info('获取官方organization启用模型数量: ' . count($officialModelIds));

            // 4. 获取所有非官方organization编码（不需要事务）
            $allOrganizationCodes = Db::table('service_provider_models')
                ->where('organization_code', '!=', $officialOrganizationCode)
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('organization_code')
                ->toArray();

            $logger->info('需要清理的organization数量: ' . count($allOrganizationCodes));

            // 5. 按organizationhandle清理工作（小事务）
            $this->cleanOrganizationsInBatches($allOrganizationCodes, $officialModelIds, $officialEnabledModels, $logger);
        } catch (Throwable $e) {
            $logger->error('清理 service_provider_models 冗余数据过程中发生error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * reset官方organization模型的 parent_id（单独事务）.
     */
    private function resetOfficialModelsParentId(string $officialOrganizationCode, LoggerInterface $logger): void
    {
        Db::transaction(function () use ($officialOrganizationCode, $logger) {
            $updatedCount = Db::table('service_provider_models')
                ->where('organization_code', $officialOrganizationCode)
                ->where('model_parent_id', '!=', 0)
                ->update(['model_parent_id' => 0]);
            $updatedCount && $logger->info("官方organization模型 model_parent_id reset为 0: {$updatedCount} 条");
        });
    }

    /**
     * 分批并发清理各个organization的数据（每个organization独立小事务）.
     */
    private function cleanOrganizationsInBatches(array $organizationCodes, array $officialModelIds, array $officialEnabledModels, LoggerInterface $logger): void
    {
        $totalDeleted = 0;
        $totalOrgs = count($organizationCodes);

        // 将organization分批handle，每批最多 5 个organization并发
        $chunks = array_chunk($organizationCodes, 5);

        foreach ($chunks as $chunkIndex => $chunk) {
            $logger->info('starthandle第 ' . ($chunkIndex + 1) . ' 批organization，数量: ' . count($chunk));

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

            // execute并发task并等待结果
            $results = $parallel->wait();

            // handle结果
            foreach ($results as $orgCode => $result) {
                if (isset($result['error'])) {
                    $logger->error("清理organization {$orgCode} 时发生error: " . $result['error']->getMessage());
                } else {
                    $deletedCount = $result['deleted_count'];
                    $totalDeleted += $deletedCount;

                    if ($deletedCount > 0) {
                        $logger->info("organization {$orgCode} delete冗余模型: {$deletedCount} 条");
                    }
                }
            }

            $logger->info('第 ' . ($chunkIndex + 1) . ' 批organizationhandlecomplete');

            // 每handle一批输出进度
            $processedCount = ($chunkIndex + 1) * 10;
            if ($processedCount > $totalOrgs) {
                $processedCount = $totalOrgs;
            }
            $logger->info("已handle {$processedCount}/{$totalOrgs} 个organization，累计delete: {$totalDeleted} 条");
        }

        $logger->info("service_provider_models 表冗余数据清理complete，总共delete: {$totalDeleted} 条记录");
    }

    /**
     * 清理单个organization的数据（单独事务）.
     */
    private function cleanSingleOrganization(string $organizationCode, array $officialModelIds, array $officialEnabledModels): array
    {
        return Db::transaction(function () use ($organizationCode, $officialModelIds, $officialEnabledModels) {
            // 获取官方organization编码用于安全防护
            $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();

            // 防护检查：确保不handle官方organization
            if ($organizationCode === $officialOrganizationCode) {
                return ['deleted_count' => 0];
            }

            $totalDeletedCount = 0;

            // 1. delete所有 is_office = 1 的数据（防护：非官方organization）
            $isOfficeDeletedCount = Db::table('service_provider_models')
                ->where('organization_code', $organizationCode)
                ->where('organization_code', '!=', $officialOrganizationCode) // 双重防护
                ->where('is_office', 1)
                ->whereNull('deleted_at')
                ->delete();
            $totalDeletedCount += $isOfficeDeletedCount;

            // 2. deletequote不存在configuration的模型（批量query和批量delete）
            $invalidConfigDeletedCount = $this->cleanModelsWithInvalidConfig($organizationCode, $officialOrganizationCode);
            $totalDeletedCount += $invalidConfigDeletedCount;

            // 3. deleteconfiguration无效的模型（configuration解密后为null或所有value都是null）
            $invalidConfigDataDeletedCount = $this->cleanModelsWithInvalidConfigData($organizationCode, $officialOrganizationCode);
            $totalDeletedCount += $invalidConfigDataDeletedCount;

            // 4. 查找 model_parent_id 不为 0 的数据
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

                // 检查 model_parent_id 是否在官方organization的模型 id 中
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

            // 5. 批量delete冗余数据（带防护）
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
     * 清理quote不存在configuration的模型（批量query和批量delete）.
     */
    private function cleanModelsWithInvalidConfig(string $organizationCode, string $officialOrganizationCode): int
    {
        // 1. 批量query该organization下所有模型的 service_provider_config_id
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

        // 5. 批量deletequote不存在configuration的模型
        return Db::table('service_provider_models')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // 双重防护
            ->whereIn('service_provider_config_id', $invalidConfigIds)
            ->delete();
    }

    /**
     * 清理configuration数据无效的模型（configuration解密后为null或所有value都是null）.
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

        // 2. 循环检查每个configuration的有效性
        foreach ($configs as $config) {
            try {
                // 解密configuration（useconfiguration ID 作为 salt）
                $decodedConfig = ProviderConfigAssembler::decodeConfig($config['config'], (string) $config['id']);

                // 检查configuration是否有效
                if ($this->isConfigDataInvalid($decodedConfig)) {
                    $invalidConfigIds[] = $config['id'];
                }
            } catch (Throwable $e) {
                // 如果解密fail，也认为是无效configuration
                $invalidConfigIds[] = $config['id'];
            }
        }

        if (empty($invalidConfigIds)) {
            return 0;
        }

        // 3. 批量deleteuse无效configuration的模型
        return Db::table('service_provider_models')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // 双重防护
            ->whereIn('service_provider_config_id', $invalidConfigIds)
            ->delete();
    }

    /**
     * 检查解密后的configuration数据是否无效.
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

        // 检查array中所有key的value是否都为null
        foreach ($decodedConfig as $key => $value) {
            // 如果有任何一个value不为null，则configuration有效
            if (! empty($value)) {
                return false;
            }
        }

        // 所有value都是null，configuration无效
        return true;
    }

    /**
     * 获取log记录器.
     */
    private function getLogger(): LoggerInterface
    {
        $container = ApplicationContext::getContainer();
        return $container->get(LoggerFactory::class)?->get('migration');
    }
};
