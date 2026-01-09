<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Persistence;

use App\Domain\Provider\Entity\ProviderEntity;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderModelQuery;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Domain\Provider\Repository\Facade\DelightfulProviderAndModelsInterface;
use App\Domain\Provider\Repository\Facade\ProviderModelRepositoryInterface;
use App\Domain\Provider\Repository\Persistence\Model\ProviderConfigModel;
use App\Domain\Provider\Repository\Persistence\Model\ProviderModelModel;
use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Interfaces\Provider\Assembler\ProviderModelAssembler;
use App\Interfaces\Provider\DTO\SaveProviderModelDTO;
use Hyperf\Codec\Json;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\Redis;

class ProviderModelRepository extends AbstractProviderModelRepository implements ProviderModelRepositoryInterface
{
    protected bool $filterOrganizationCode = true;

    public function __construct(
        private readonly DelightfulProviderAndModelsInterface $delightfulProviderAndModels,
    ) {
    }

    public function getAvailableByModelIdOrId(ProviderDataIsolation $dataIsolation, string $modelId, bool $checkStatus = true): ?ProviderModelEntity
    {
        $builder = $this->createBuilder($dataIsolation, ProviderModelModel::query());
        if (is_numeric($modelId)) {
            $builder->where('id', $modelId);
        } else {
            $builder->where('model_id', $modelId);
        }
        $checkStatus && $builder->where('status', Status::Enabled->value);
        $result = Db::select($builder->toSql(), $builder->getBindings());
        if (! isset($result[0])) {
            return null;
        }
        return ProviderModelAssembler::toEntity($result[0]);
    }

    public function getById(ProviderDataIsolation $dataIsolation, string $id): ProviderModelEntity
    {
        $builder = $this->createBuilder($dataIsolation, ProviderModelModel::query());
        $builder->where('id', $id);

        $result = Db::select($builder->toSql(), $builder->getBindings());
        if (empty($result)) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotFound);
        }
        return ProviderModelAssembler::toEntity($result[0]);
    }

    public function getByModelId(ProviderDataIsolation $dataIsolation, string $modelId): ?ProviderModelEntity
    {
        $builder = $this->createBuilder($dataIsolation, ProviderModelModel::query());
        $builder->where('model_id', $modelId);

        $result = Db::select($builder->toSql(), $builder->getBindings());
        if (empty($result)) {
            return null;
        }
        return ProviderModelAssembler::toEntity($result[0]);
    }

    /**
     * @return ProviderModelEntity[]
     */
    public function getByProviderConfigId(ProviderDataIsolation $dataIsolation, string $providerConfigId): array
    {
        $builder = $this->createProviderModelQuery()
            ->where('organization_code', $dataIsolation->getCurrentOrganizationCode())
            ->where('service_provider_config_id', $providerConfigId);

        $result = Db::select($builder->toSql(), $builder->getBindings());

        return ProviderModelAssembler::toEntities($result);
    }

    public function deleteByProviderId(ProviderDataIsolation $dataIsolation, string $providerId): void
    {
        $builder = ProviderModelModel::query()->where('organization_code', $dataIsolation->getCurrentOrganizationCode());
        $builder->where('service_provider_config_id', $providerId)->delete();
    }

    public function deleteById(ProviderDataIsolation $dataIsolation, string $id): void
    {
        $builder = ProviderModelModel::query()->where('organization_code', $dataIsolation->getCurrentOrganizationCode());
        $builder->where('id', $id)->delete();
    }

    public function saveModel(ProviderDataIsolation $dataIsolation, SaveProviderModelDTO $dto): ProviderModelEntity
    {
        // settingorganizationencoding(priorityuseDTOmiddleorganizationencoding,nothenusecurrentdataisolationmiddle)
        $dto->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());

        $data = $dto->toArray();
        $entity = new ProviderModelEntity($data);

        if ($dto->getId()) {
            // prepareupdatedata,onlycontainhavechangefield
            $updateData = $this->serializeEntityToArray($entity);
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $success = ProviderModelModel::query()
                ->where('organization_code', $dataIsolation->getCurrentOrganizationCode())
                ->where('id', $dto->getId())
                ->update($updateData);
            if ($success === 0) {
                ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotFound);
            }
            // firstquerydatabaseshowhaveactualbody
            return $this->getById($dataIsolation, $dto->getId());
        }
        return $this->create($dataIsolation, $entity);
    }

    /**
     * updatemodelstatus(supportwriteo clockcopylogic).
     */
    public function updateStatus(ProviderDataIsolation $dataIsolation, string $id, Status $status): void
    {
        // 1. by id querymodelwhetherexistsin(notlimitorganization)
        $model = $this->getModelByIdWithoutOrgFilter($id);
        if (! $model) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotFound);
        }

        $currentOrganizationCode = $dataIsolation->getCurrentOrganizationCode();
        $modelOrganizationCode = $model->getOrganizationCode();

        // 2. judgemodelbelong toorganizationwhetherandcurrentorganizationoneto
        if ($modelOrganizationCode !== $currentOrganizationCode) {
            // organizationnotoneto:judgemodelbelong toorganizationwhetherisofficialorganization
            if ($this->isOfficialOrganization($modelOrganizationCode)
                && ! $this->isOfficialOrganization($currentOrganizationCode)) {
                // modelbelongatofficialorganizationandcurrentorganizationnotisofficialorganization:walk writeo clockcopylogic
                $organizationModelId = $this->delightfulProviderAndModels->updateDelightfulModelStatus($dataIsolation, $model);
            } else {
                // othersituation:nopermissionoperationas
                ExceptionBuilder::throw(ServiceProviderErrorCode::ModelNotFound);
            }
        } else {
            $organizationModelId = $id;
        }
        // 3. updateorganizationmodelstatus
        $this->updateStatusDirect($dataIsolation, $organizationModelId, $status);
    }

    public function deleteByModelParentId(ProviderDataIsolation $dataIsolation, string $modelParentId): void
    {
        $builder = ProviderModelModel::query()->where('organization_code', $dataIsolation->getCurrentOrganizationCode());
        $builder->where('model_parent_id', $modelParentId)->delete();
    }

    public function deleteByModelParentIds(ProviderDataIsolation $dataIsolation, array $modelParentIds): void
    {
        $modelParentIds = array_values(array_unique($modelParentIds));
        if (empty($modelParentIds)) {
            return;
        }
        $builder = ProviderModelModel::query()->where('organization_code', $dataIsolation->getCurrentOrganizationCode());
        $builder->whereIn('model_parent_id', $modelParentIds)->delete();
    }

    /**
     * pass service_provider_config_id getmodelcolumntable.
     * @param string $configId maybeistemplate id,such as ProviderConfigIdAssembler
     * @return ProviderModelEntity[]
     */
    public function getProviderModelsByConfigId(ProviderDataIsolation $dataIsolation, string $configId, ProviderEntity $providerEntity): array
    {
        // ifisofficialservicequotient,needconductdatamergeandstatusjudge
        if ($providerEntity->getProviderCode() === ProviderCode::Official && ! OfficialOrganizationUtil::isOfficialOrganization($dataIsolation->getCurrentOrganizationCode())) {
            return $this->delightfulProviderAndModels->getDelightfulEnableModels($dataIsolation->getCurrentOrganizationCode(), $providerEntity->getCategory());
        }

        // nonofficialservicequotient,by originallogicqueryfingersetconfigurationdownmodel
        if (! is_numeric($configId)) {
            return [];
        }
        $modelsBuilder = $this->createProviderModelQuery()
            ->where('organization_code', $dataIsolation->getCurrentOrganizationCode())
            ->where('service_provider_config_id', $configId);

        $result = Db::select($modelsBuilder->toSql(), $modelsBuilder->getBindings());
        return ProviderModelAssembler::toEntities($result);
    }

    /**
     * getorganizationcanusemodelcolumntable(containorganizationfromselfmodelandDelightfulmodel).
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param null|Category $category modelcategory,fornullo clockreturn havecategorymodel
     * @return ProviderModelEntity[] bysortdescendingsortmodelcolumntable,containorganizationmodelandDelightfulmodel(notgoreload)
     */
    public function getModelsForOrganization(ProviderDataIsolation $dataIsolation, ?Category $category = null, ?Status $status = Status::Enabled): array
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // generatecachekey
        $cacheKey = sprintf('provider_models:available:%s:%s', $organizationCode, $category->value ?? 'all');

        // tryfromcacheget
        $redis = di(Redis::class);
        $cachedData = $redis->get($cacheKey);

        if ($cachedData !== false) {
            // fromcacherestoreactualbodyobject
            $modelsArray = Json::decode($cachedData);
            $allModels = [];
            foreach ($modelsArray as $modelData) {
                $allModels[] = new ProviderModelEntity($modelData);
            }
            return $allModels;
        }

        // cachenotcommandmiddle,executeoriginallogic
        // 1. firstqueryorganizationdownenableservicequotientconfigurationID
        $builder = ProviderConfigModel::query();

        if ($status !== null) {
            $builder->where('status', $status->value);
        }

        $enabledConfigQuery = $builder
            ->where('organization_code', $organizationCode)
            ->whereNull('deleted_at')
            ->select('id');
        $enabledConfigIds = Db::select($enabledConfigQuery->toSql(), $enabledConfigQuery->getBindings());
        $enabledConfigIdArray = array_column($enabledConfigIds, 'id');

        // 2. useenableconfigurationIDqueryorganizationfromselfenablemodel
        $organizationModels = [];
        if (! empty($enabledConfigIdArray)) {
            $organizationModelsBuilder = $this->createProviderModelQuery()
                ->where('organization_code', $organizationCode)
                ->whereIn('service_provider_config_id', $enabledConfigIdArray);
            if (! OfficialOrganizationUtil::isOfficialOrganization($organizationCode)) {
                // querynormalorganizationfromselfmodel. officialorganizationmodelshowin model_parent_id equalitfromself,needwashdata.
                $organizationModelsBuilder->where('model_parent_id', 0);
            }
            // iffingersetcategory,addcategoryfiltercondition
            if ($category !== null) {
                $organizationModelsBuilder->where('category', $category->value);
            }

            if ($status !== null) {
                $builder->where('status', $status->value);
            }

            $organizationModelsResult = Db::select($organizationModelsBuilder->toSql(), $organizationModelsBuilder->getBindings());
            $organizationModels = ProviderModelAssembler::toEntities($organizationModelsResult);
        }

        // 3. getDelightfulmodel(ifnotisofficialorganization)
        $delightfulModels = [];
        if (! OfficialOrganizationUtil::isOfficialOrganization($organizationCode)) {
            $delightfulModels = $this->delightfulProviderAndModels->getDelightfulEnableModels($organizationCode, $category);
        }

        // 4. directlymergemodelcolumntable,notgoreload
        $allModels = array_merge($organizationModels, $delightfulModels);

        // 5. bysortdescendingsort
        usort($allModels, static function ($a, $b) {
            return $b->getSort() <=> $a->getSort();
        });
        // 6. filterstatus
        if ($status !== null) {
            $allModels = array_filter($allModels, static function (ProviderModelEntity $model) use ($status) {
                return $model->getStatus() === $status;
            });
        }
        // 7. transferforarrayandcacheresult,cache10second
        $modelsArray = [];
        foreach ($allModels as $model) {
            $modelsArray[] = $model->toArray();
        }
        $redis->setex($cacheKey, 10, Json::encode($modelsArray));

        return $allModels;
    }

    public function getByIds(ProviderDataIsolation $dataIsolation, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $builder = $this->createBuilder($dataIsolation, ProviderModelModel::query())
            ->whereIn('id', $ids);

        $result = Db::select($builder->toSql(), $builder->getBindings());

        $entities = ProviderModelAssembler::toEntities($result);

        // convertforbyIDforkeyarray
        $modelsById = [];
        foreach ($entities as $entity) {
            $modelsById[(string) $entity->getId()] = $entity;
        }

        return $modelsById;
    }

    public function getByModelIds(ProviderDataIsolation $dataIsolation, array $modelIds): array
    {
        if (empty($modelIds)) {
            return [];
        }

        $builder = $this->createBuilder($dataIsolation, ProviderModelModel::query())
            ->whereIn('model_id', $modelIds)
            ->orderBy('status', 'desc') // prioritysort:enablestatusinfront
            ->orderBy('id'); // itstimebyIDsort,guaranteeresultonetoproperty

        $result = Db::select($builder->toSql(), $builder->getBindings());
        $entities = ProviderModelAssembler::toEntities($result);

        // convertforbymodel_idforkeyarray,retain havemodel
        $modelsByModelId = [];
        foreach ($entities as $entity) {
            $modelId = $entity->getModelId();
            if (! isset($modelsByModelId[$modelId])) {
                $modelsByModelId[$modelId] = [];
            }
            $modelsByModelId[$modelId][] = $entity;
        }

        return $modelsByModelId;
    }

    /**
     * according toIDquerymodel(notlimitorganization).
     */
    public function getModelByIdWithoutOrgFilter(string $id): ?ProviderModelEntity
    {
        $query = $this->createProviderModelQuery()
            ->where('id', $id);
        $result = Db::select($query->toSql(), $query->getBindings());

        if (empty($result)) {
            return null;
        }

        return ProviderModelAssembler::toEntity($result[0]);
    }

    /**
     * @return array{total: int, list: ProviderModelEntity[]}
     */
    public function queries(ProviderDataIsolation $dataIsolation, ProviderModelQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, ProviderModelModel::query());
        if (! is_null($query->getModelIds())) {
            $builder->whereIn('model_id', $query->getModelIds());
        }
        if (! is_null($query->getStatus())) {
            $builder->where('status', $query->getStatus()->value);
        }
        if (! is_null($query->getModelType())) {
            $builder->where('model_type', $query->getModelType()->value);
        }

        $data = $this->getByPage($builder, $page, $query);
        $list = [];
        /** @var ProviderModelModel $model */
        foreach ($data['list'] as $model) {
            $entity = ProviderModelAssembler::toEntity($model->toArray());
            match ($query->getKeyBy()) {
                'id' => $list[$entity->getId()] = $entity,
                'model_id' => $list[$entity->getModelId()] = $entity,
                default => $list[] = $entity,
            };
        }
        $data['list'] = $list;
        return $data;
    }

    /**
     * according toqueryconditiongetbymodeltypegroupmodelIDcolumntable.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param ProviderModelQuery $query querycondition
     * @return array<string, array<string>> bymodeltypegroupmodelIDarray,format: [modelType => [model_id, model_id]]
     */
    public function getModelIdsGroupByType(ProviderDataIsolation $dataIsolation, ProviderModelQuery $query): array
    {
        $builder = $this->createBuilder($dataIsolation, ProviderModelModel::query());

        // applicationquerycondition
        if (! is_null($query->getModelIds())) {
            $builder->whereIn('model_id', $query->getModelIds());
        }
        if (! is_null($query->getStatus())) {
            $builder->where('status', $query->getStatus()->value);
        }
        if (! is_null($query->getModelType())) {
            $builder->where('model_type', $query->getModelType()->value);
        }

        // choose model_id and model_type field
        $builder->select('model_id', 'model_type');

        // applicationsort
        if (! empty($query->getOrder())) {
            foreach ($query->getOrder() as $field => $direction) {
                $builder->orderBy($field, $direction);
            }
        }

        $result = Db::select($builder->toSql(), $builder->getBindings());

        // bymodeltypegroup,andgoreloadmodelID
        $groupedResults = [];
        foreach ($result as $row) {
            $modelType = $row['model_type'];
            $modelId = $row['model_id'];

            if (! isset($groupedResults[$modelType])) {
                $groupedResults[$modelType] = [];
            }

            // avoidduplicatemodelID
            if (! in_array($modelId, $groupedResults[$modelType], true)) {
                $groupedResults[$modelType][] = $modelId;
            }
        }

        return $groupedResults;
    }

    /**
     * directlyupdatemodelstatus.
     */
    private function updateStatusDirect(ProviderDataIsolation $dataIsolation, string $id, Status $status): void
    {
        $builder = ProviderModelModel::query()->where('organization_code', $dataIsolation->getCurrentOrganizationCode());
        $builder->where('id', $id)->update(['status' => $status->value]);
    }

    /**
     * preparemoveexceptsoft deleteclosefeature,temporarythishow to write.createwithhavesoftdeletefilter ProviderModelModel querybuilddevice.
     */
    private function createProviderModelQuery(): Builder
    {
        /* @phpstan-ignore-next-line */
        return ProviderModelModel::query()->whereNull('deleted_at');
    }

    /**
     * whetherisofficialorganization.
     */
    private function isOfficialOrganization(string $organizationCode): bool
    {
        return OfficialOrganizationUtil::isOfficialOrganization($organizationCode);
    }
}
