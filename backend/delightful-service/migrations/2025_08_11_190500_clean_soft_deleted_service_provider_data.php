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
        // clearnullofficialorganization Delightful servicequotientconfigurationandmodel(putindeletesoftdeletedataoffront)
        $this->cleanOfficialDelightfulProviderData();

        // cleanup service_provider 相closefour張tablemiddlesoftdeletedata
        $this->cleanSoftDeletedData();

        // clean service_provider tablemiddle provider_code='Official' record
        $this->cleanOfficialProviderData();

        // cleanup service_provider_models tablemiddleredundantremainderdata
        $this->cleanServiceProviderModelsData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // softdeletedataoneonce physicaldeletethennomethodrestore, by down methodfornull
    }

    /**
     * cleanup service_provider 相closetablemiddlesoftdeletedata.
     */
    private function cleanSoftDeletedData(): void
    {
        $logger = $this->getLogger();
        $logger->info('startcleanup service_provider 相closetablesoftdeletedata');

        try {
            // usetransactionensuredataonetoproperty
            Db::transaction(function () use ($logger) {
                $totalDeleted = 0;

                // 1. delete service_provider tablemiddlesoftdeletedata
                $deletedCount = Db::table('service_provider')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider tablesoftdeletedata: {$deletedCount} item");

                // 2. delete service_provider_configs tablemiddlesoftdeletedata
                $deletedCount = Db::table('service_provider_configs')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_configs tablesoftdeletedata: {$deletedCount} item");

                // 3. delete service_provider_models tablemiddlesoftdeletedata
                $deletedCount = Db::table('service_provider_models')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_models tablesoftdeletedata: {$deletedCount} item");

                // 4. delete service_provider_original_models tablemiddlesoftdeletedata
                $deletedCount = Db::table('service_provider_original_models')
                    ->whereNotNull('deleted_at')
                    ->delete();
                $totalDeleted += $deletedCount;
                $logger->info("delete service_provider_original_models tablesoftdeletedata: {$deletedCount} item");

                $logger->info("service_provider 相closetablesoftdeletedatacleanupcomplete,totaldelete: {$totalDeleted} itemrecord");
            });
        } catch (Throwable $e) {
            $logger->error('cleanupsoftdeletedataproceduremiddlehairgenerateerror: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * clearnullofficialorganization Delightful servicequotientconfigurationandmodel.
     */
    private function cleanOfficialDelightfulProviderData(): void
    {
        $logger = $this->getLogger();
        $logger->info('startclearnullofficialorganization Delightful servicequotientconfigurationandmodel');

        try {
            // usetransactionensuredataonetoproperty
            Db::transaction(function () use ($logger) {
                // getofficialorganizationencoding
                $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();
                $logger->info("officialorganizationencoding: {$officialOrganizationCode}");

                $totalDeleted = 0;

                // 1. findofficialorganizationmiddle Delightful servicequotientconfigurationID
                $delightfulProviderConfigQuery = Db::table('service_provider_configs as configs')
                    ->join('service_provider as providers', 'configs.service_provider_id', '=', 'providers.id')
                    ->select('configs.id')
                    ->where('configs.organization_code', $officialOrganizationCode)
                    ->where('providers.provider_code', 'Official');

                $delightfulProviderConfigs = Db::select($delightfulProviderConfigQuery->toSql(), $delightfulProviderConfigQuery->getBindings());
                $delightfulConfigIds = array_column($delightfulProviderConfigs, 'id');

                if (! empty($delightfulConfigIds)) {
                    $logger->info('findto Delightful servicequotientconfigurationquantity: ' . count($delightfulConfigIds));

                    // 2. deleteofficialorganizationmiddle Delightful servicequotientdownmodel
                    $deletedModelsCount = Db::table('service_provider_models')
                        ->where('organization_code', $officialOrganizationCode)
                        ->whereIn('service_provider_config_id', $delightfulConfigIds)
                        ->delete();
                    $totalDeleted += $deletedModelsCount;
                    $logger->info("deleteofficialorganization Delightful servicequotientmodel: {$deletedModelsCount} item");

                    // 3. deleteofficialorganization Delightful servicequotientconfiguration
                    $deletedConfigsCount = Db::table('service_provider_configs')
                        ->where('organization_code', $officialOrganizationCode)
                        ->whereIn('id', $delightfulConfigIds)
                        ->delete();
                    $totalDeleted += $deletedConfigsCount;
                    $logger->info("deleteofficialorganization Delightful servicequotientconfiguration: {$deletedConfigsCount} item");
                } else {
                    $logger->info('notfindtoneedcleanup Delightful servicequotientconfiguration');
                }

                // 4. quotaoutsidecleanup:delete have is_office=1 officialorganizationmodel
                $deletedOfficeModelsCount = Db::table('service_provider_models')
                    ->where('organization_code', $officialOrganizationCode)
                    ->where('is_office', 1)
                    ->delete();
                $totalDeleted += $deletedOfficeModelsCount;
                $logger->info("deleteofficialorganization Delightful model(is_office=1): {$deletedOfficeModelsCount} item");

                $logger->info("officialorganization Delightful servicequotientdatacleanupcomplete,totaldelete: {$totalDeleted} itemrecord");
            });
        } catch (Throwable $e) {
            $logger->error('cleanupofficialorganization Delightful servicequotientdataproceduremiddlehairgenerateerror: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * clean Official servicequotientdata.
     */
    private function cleanOfficialProviderData(): void
    {
        $logger = $this->getLogger();
        $logger->info('startclean Official servicequotient description and translate field');

        try {
            // usetransactionensuredataonetoproperty
            Db::transaction(function () use ($logger) {
                // queryneedclean Official servicequotientrecord
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

                $logger->info("cleancomplete,totalimpactlinecount: {$updateCount}");
                $logger->info('Official servicequotientdatacleancomplete');
            });
        } catch (Throwable $e) {
            $logger->error('datacleanproceduremiddlehairgenerateerror: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * cleanup service_provider_models tablemiddleredundantremainderdata.
     */
    private function cleanServiceProviderModelsData(): void
    {
        $logger = $this->getLogger();
        $logger->info('startcleanup service_provider_models tableredundantremainderdata');

        try {
            // 1. getofficialorganizationencoding
            $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();
            $logger->info("officialorganizationencoding: {$officialOrganizationCode}");

            // 2. single独transaction:resetofficialorganizationmodel model_parent_id
            $this->resetOfficialModelsParentId($officialOrganizationCode, $logger);

            // 3. getofficialorganization haveenablemodel(notneedtransaction)
            $officialEnabledModels = Db::table('service_provider_models')
                ->where('organization_code', $officialOrganizationCode)
                ->where('status', Status::Enabled->value)
                ->whereNull('deleted_at')
                ->select(['id', 'status'])
                ->get()
                ->keyBy('id')
                ->toArray();

            $officialModelIds = array_keys($officialEnabledModels);
            $logger->info('getofficialorganizationenablemodelquantity: ' . count($officialModelIds));

            // 4. get havenonofficialorganizationencoding(notneedtransaction)
            $allOrganizationCodes = Db::table('service_provider_models')
                ->where('organization_code', '!=', $officialOrganizationCode)
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('organization_code')
                ->toArray();

            $logger->info('needcleanuporganizationquantity: ' . count($allOrganizationCodes));

            // 5. 按organizationhandlecleanupwork(smalltransaction)
            $this->cleanOrganizationsInBatches($allOrganizationCodes, $officialModelIds, $officialEnabledModels, $logger);
        } catch (Throwable $e) {
            $logger->error('cleanup service_provider_models redundantremainderdataproceduremiddlehairgenerateerror: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * resetofficialorganizationmodel parent_id(single独transaction).
     */
    private function resetOfficialModelsParentId(string $officialOrganizationCode, LoggerInterface $logger): void
    {
        Db::transaction(function () use ($officialOrganizationCode, $logger) {
            $updatedCount = Db::table('service_provider_models')
                ->where('organization_code', $officialOrganizationCode)
                ->where('model_parent_id', '!=', 0)
                ->update(['model_parent_id' => 0]);
            $updatedCount && $logger->info("officialorganizationmodel model_parent_id resetfor 0: {$updatedCount} item");
        });
    }

    /**
     * minutebatchandhaircleanupeachorganizationdata(eachorganizationindependentsmalltransaction).
     */
    private function cleanOrganizationsInBatches(array $organizationCodes, array $officialModelIds, array $officialEnabledModels, LoggerInterface $logger): void
    {
        $totalDeleted = 0;
        $totalOrgs = count($organizationCodes);

        // willorganizationminutebatchhandle,eachbatchat most 5 organizationandhair
        $chunks = array_chunk($organizationCodes, 5);

        foreach ($chunks as $chunkIndex => $chunk) {
            $logger->info('starthandlethe ' . ($chunkIndex + 1) . ' batchorganization,quantity: ' . count($chunk));

            $parallel = new Parallel(10);

            // addandhairtask
            foreach ($chunk as $organizationCode) {
                $parallel->add(function () use ($organizationCode, $officialModelIds, $officialEnabledModels) {
                    try {
                        return $this->cleanSingleOrganization($organizationCode, $officialModelIds, $officialEnabledModels);
                    } catch (Throwable $e) {
                        return ['error' => $e, 'org_code' => $organizationCode];
                    }
                }, $organizationCode);
            }

            // executeandhairtaskandetc待result
            $results = $parallel->wait();

            // handleresult
            foreach ($results as $orgCode => $result) {
                if (isset($result['error'])) {
                    $logger->error("cleanuporganization {$orgCode} o clockhairgenerateerror: " . $result['error']->getMessage());
                } else {
                    $deletedCount = $result['deleted_count'];
                    $totalDeleted += $deletedCount;

                    if ($deletedCount > 0) {
                        $logger->info("organization {$orgCode} deleteredundantremaindermodel: {$deletedCount} item");
                    }
                }
            }

            $logger->info('the ' . ($chunkIndex + 1) . ' batchorganizationhandlecomplete');

            // eachhandleonebatchoutputenterdegree
            $processedCount = ($chunkIndex + 1) * 10;
            if ($processedCount > $totalOrgs) {
                $processedCount = $totalOrgs;
            }
            $logger->info("alreadyhandle {$processedCount}/{$totalOrgs} organization,accumulateddelete: {$totalDeleted} item");
        }

        $logger->info("service_provider_models tableredundantremainderdatacleanupcomplete,totaldelete: {$totalDeleted} itemrecord");
    }

    /**
     * cleanupsingleorganizationdata(single独transaction).
     */
    private function cleanSingleOrganization(string $organizationCode, array $officialModelIds, array $officialEnabledModels): array
    {
        return Db::transaction(function () use ($organizationCode, $officialModelIds, $officialEnabledModels) {
            // getofficialorganizationencodinguseatsecurityguard
            $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();

            // guardcheck:ensurenothandleofficialorganization
            if ($organizationCode === $officialOrganizationCode) {
                return ['deleted_count' => 0];
            }

            $totalDeletedCount = 0;

            // 1. delete have is_office = 1 data(guard:nonofficialorganization)
            $isOfficeDeletedCount = Db::table('service_provider_models')
                ->where('organization_code', $organizationCode)
                ->where('organization_code', '!=', $officialOrganizationCode) // double重guard
                ->where('is_office', 1)
                ->whereNull('deleted_at')
                ->delete();
            $totalDeletedCount += $isOfficeDeletedCount;

            // 2. deletequotenotexistsinconfigurationmodel(batchquantityqueryandbatchquantitydelete)
            $invalidConfigDeletedCount = $this->cleanModelsWithInvalidConfig($organizationCode, $officialOrganizationCode);
            $totalDeletedCount += $invalidConfigDeletedCount;

            // 3. deleteconfigurationinvalidmodel(configurationdecryptbackfornullor havevalueallisnull)
            $invalidConfigDataDeletedCount = $this->cleanModelsWithInvalidConfigData($organizationCode, $officialOrganizationCode);
            $totalDeletedCount += $invalidConfigDataDeletedCount;

            // 4. find model_parent_id notfor 0 data
            $modelsWithParent = Db::table('service_provider_models')
                ->where('organization_code', $organizationCode)
                ->where('model_parent_id', '!=', 0)
                ->where('organization_code', '!=', $officialOrganizationCode) // guard
                ->whereNull('deleted_at')
                ->select(['id', 'model_parent_id', 'status'])
                ->get();

            $deleteIds = [];

            foreach ($modelsWithParent as $model) {
                $parentId = $model['model_parent_id'];

                // check model_parent_id whetherinofficialorganizationmodel id middle
                if (! in_array($parentId, $officialModelIds)) {
                    // model_parent_id inofficialorganizationfindnotto,markdelete
                    $deleteIds[] = $model['id'];
                    continue;
                }

                // if model_parent_id existsin,butstatusandofficialorganizationoneto,alsodelete
                $officialModel = $officialEnabledModels[$parentId] ?? null;
                if ($officialModel && $model['status'] == $officialModel['status']) {
                    $deleteIds[] = $model['id'];
                }
            }

            // 5. batchquantitydeleteredundantremainderdata(withguard)
            if (! empty($deleteIds)) {
                $redundantDeletedCount = Db::table('service_provider_models')
                    ->whereIn('id', $deleteIds)
                    ->where('organization_code', '!=', $officialOrganizationCode) // quotaoutsideguard
                    ->delete();
                $totalDeletedCount += $redundantDeletedCount;
            }

            return ['deleted_count' => $totalDeletedCount];
        });
    }

    /**
     * cleanupquotenotexistsinconfigurationmodel(batchquantityqueryandbatchquantitydelete).
     */
    private function cleanModelsWithInvalidConfig(string $organizationCode, string $officialOrganizationCode): int
    {
        // 1. batchquantityquerytheorganizationdown havemodel service_provider_config_id
        $modelConfigs = Db::table('service_provider_models')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // guard
            ->whereNull('deleted_at')
            ->whereNotNull('service_provider_config_id')
            ->select(['id', 'service_provider_config_id'])
            ->get();

        if ($modelConfigs->isEmpty()) {
            return 0;
        }

        // 2. extract haveuniqueone config_id
        $configIds = $modelConfigs->pluck('service_provider_config_id')->unique()->filter()->toArray();

        if (empty($configIds)) {
            return 0;
        }

        // 3. batchquantityqueryexistsin config_id
        $existingConfigIds = Db::table('service_provider_configs')
            ->whereIn('id', $configIds)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        // 4. findoutnotexistsin config_id
        $invalidConfigIds = array_diff($configIds, $existingConfigIds);

        if (empty($invalidConfigIds)) {
            return 0;
        }

        // 5. batchquantitydeletequotenotexistsinconfigurationmodel
        return Db::table('service_provider_models')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // double重guard
            ->whereIn('service_provider_config_id', $invalidConfigIds)
            ->delete();
    }

    /**
     * cleanupconfigurationdatainvalidmodel(configurationdecryptbackfornullor havevalueallisnull).
     */
    private function cleanModelsWithInvalidConfigData(string $organizationCode, string $officialOrganizationCode): int
    {
        // 1. querytheorganizationdown haveconfiguration
        $configs = Db::table('service_provider_configs')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // guard
            ->whereNull('deleted_at')
            ->select(['id', 'config'])
            ->get();

        $invalidConfigIds = [];

        // 2. loopcheckeachconfigurationvalidproperty
        foreach ($configs as $config) {
            try {
                // decryptconfiguration(useconfiguration ID asfor salt)
                $decodedConfig = ProviderConfigAssembler::decodeConfig($config['config'], (string) $config['id']);

                // checkconfigurationwhethervalid
                if ($this->isConfigDataInvalid($decodedConfig)) {
                    $invalidConfigIds[] = $config['id'];
                }
            } catch (Throwable $e) {
                // ifdecryptfail,also认forisinvalidconfiguration
                $invalidConfigIds[] = $config['id'];
            }
        }

        if (empty($invalidConfigIds)) {
            return 0;
        }

        // 3. batchquantitydeleteuseinvalidconfigurationmodel
        return Db::table('service_provider_models')
            ->where('organization_code', $organizationCode)
            ->where('organization_code', '!=', $officialOrganizationCode) // double重guard
            ->whereIn('service_provider_config_id', $invalidConfigIds)
            ->delete();
    }

    /**
     * checkdecryptbackconfigurationdatawhetherinvalid.
     * @param mixed $decodedConfig
     */
    private function isConfigDataInvalid($decodedConfig): bool
    {
        // notisarray
        if (! is_array($decodedConfig)) {
            return true;
        }

        // arrayfornull
        if (empty($decodedConfig)) {
            return true;
        }

        // checkarraymiddle havekeyvaluewhetherallfornull
        foreach ($decodedConfig as $key => $value) {
            // ifhaveanyonevaluenotfornull,thenconfigurationvalid
            if (! empty($value)) {
                return false;
            }
        }

        //  havevalueallisnull,configurationinvalid
        return true;
    }

    /**
     * getlogrecorddevice.
     */
    private function getLogger(): LoggerInterface
    {
        $container = ApplicationContext::getContainer();
        return $container->get(LoggerFactory::class)?->get('migration');
    }
};
