<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Persistence;

use App\Domain\Provider\DTO\ProviderConfigDTO;
use App\Domain\Provider\Entity\ProviderConfigEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderConfigQuery;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Domain\Provider\Repository\Facade\ProviderConfigRepositoryInterface;
use App\Domain\Provider\Repository\Persistence\Model\ProviderConfigModel;
use App\Domain\Provider\Repository\Persistence\Model\ProviderModel;
use App\Domain\Provider\Repository\Persistence\Model\ProviderModelModel;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\Aes\AesUtil;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Interfaces\Provider\Assembler\ProviderConfigAssembler;
use DateTime;
use Hyperf\Codec\Json;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;

use function Hyperf\Config\config;

class ProviderConfigRepository extends AbstractModelRepository implements ProviderConfigRepositoryInterface
{
    protected bool $filterOrganizationCode = true;

    public function __construct(
        private readonly ProviderTemplateRepository $providerTemplateRepository,
        protected ProviderConfigModel $configModel,
        protected ProviderModelModel $serviceProviderModelsModel,
        protected ProviderModel $serviceProviderModel
    ) {
        parent::__construct($configModel, $serviceProviderModelsModel, $serviceProviderModel);
    }

    public function getById(ProviderDataIsolation $dataIsolation, int $id): ?ProviderConfigEntity
    {
        $builder = $this->createConfigQuery()->where('organization_code', $dataIsolation->getCurrentOrganizationCode());

        $builder->where('id', $id);

        $result = Db::select($builder->toSql(), $builder->getBindings());

        if (empty($result)) {
            return null;
        }

        return ProviderConfigAssembler::toEntity($result[0]);
    }

    /**
     * @param array<int> $ids
     * @return array<int, ProviderConfigEntity>
     */
    public function getByIds(ProviderDataIsolation $dataIsolation, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $builder = $this->createConfigQuery()->where('organization_code', $dataIsolation->getCurrentOrganizationCode());
        $ids = array_values(array_unique($ids));
        $builder->whereIn('id', $ids);
        $result = Db::select($builder->toSql(), $builder->getBindings());

        $entities = [];
        foreach ($result as $model) {
            $entities[$model['id']] = ProviderConfigAssembler::toEntity($model);
        }

        return $entities;
    }

    /**
     * @return array{total: int, list: array<ProviderConfigEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, ProviderConfigQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, ProviderConfigModel::query());

        if (! is_null($query->getStatus())) {
            $builder->where('status', $query->getStatus()->value);
        }
        if (! is_null($query->getIds())) {
            $builder->whereIn('id', $query->getIds());
        }

        // addsort(numbermorebigmore靠front)
        $builder->orderBy('sort', 'DESC')->orderBy('id', 'ASC');

        $result = $this->getByPage($builder, $page, $query);

        $list = [];
        /** @var ProviderConfigModel $model */
        foreach ($result['list'] as $model) {
            $entity = ProviderConfigAssembler::toEntity($model->toArray());
            if ($query->getKeyBy() === 'id') {
                $list[$entity->getId()] = $entity;
            } else {
                $list[] = $entity;
            }
        }
        $result['list'] = $list;

        return $result;
    }

    public function save(ProviderDataIsolation $dataIsolation, ProviderConfigEntity $providerConfigEntity): ProviderConfigEntity
    {
        $attributes = $this->getFieldAttributes($providerConfigEntity);

        // judgeiscreatealsoismorenew标志
        $isNewRecord = ! $providerConfigEntity->getId();

        if ($isNewRecord) {
            // createnewrecord - 先generateID
            $this->initializeEntityForCreation($providerConfigEntity, $attributes);
        } else {
            // update现haverecord
            $now = new DateTime();
            $providerConfigEntity->setUpdatedAt($now);
            $attributes['updated_at'] = $now->format('Y-m-d H:i:s');

            // update操aso clock,移exceptnotshouldbemorenewfield
            unset($attributes['id'], $attributes['created_at']);
        }

        // toconfigurationdataconductencrypt(if存inandnotencrypt)
        if (! empty($attributes['config'])) {
            $configId = (string) $providerConfigEntity->getId();

            // if config isstringandisvalid JSON format(notencryptconfigurationdata),thenneedencrypt
            if (is_string($attributes['config']) && json_validate($attributes['config'])) {
                $decodedConfig = Json::decode($attributes['config']);
                $attributes['config'] = ProviderConfigAssembler::encodeConfig($decodedConfig, $configId);
            }
        }

        if ($isNewRecord) {
            // createnewrecord
            ProviderConfigModel::query()->insert($attributes);
        } else {
            // update现haverecord
            ProviderConfigModel::query()
                ->where('id', $providerConfigEntity->getId())
                ->update($attributes);
        }

        return $providerConfigEntity;
    }

    public function delete(ProviderDataIsolation $dataIsolation, string $id): void
    {
        $builder = $this->createConfigQuery()->where('organization_code', $dataIsolation->getCurrentOrganizationCode());
        $builder->where('id', $id)->delete();
    }

    public function findFirstByServiceProviderId(ProviderDataIsolation $dataIsolation, int $serviceProviderId): ?ProviderConfigEntity
    {
        $query = $this->createConfigQuery()->where('organization_code', $dataIsolation->getCurrentOrganizationCode());
        $query->where('service_provider_id', $serviceProviderId)
            ->orderBy('id')
            ->limit(1);

        $result = Db::select($query->toSql(), $query->getBindings());

        if (empty($result)) {
            return null;
        }

        return ProviderConfigAssembler::toEntity($result[0]);
    }

    /**
     * @return ProviderConfigEntity[]
     */
    public function getsByServiceProviderIdsAndOffice(array $serviceProviderIds): array
    {
        if (empty($serviceProviderIds)) {
            return [];
        }
        $query = $this->createConfigQuery()
            ->whereIn('service_provider_id', $serviceProviderIds)
            ->where('organization_code', OfficialOrganizationUtil::getOfficialOrganizationCode());
        $result = Db::select($query->toSql(), $query->getBindings());
        return ProviderConfigAssembler::toEntities($result);
    }

    public function deleteById(string $id): void
    {
        ProviderConfigModel::query()->where('id', $id)->delete();
    }

    /**
     * according toorganizationandservicequotienttypegetservicequotientconfigurationcolumn表.
     * new逻辑:bydatabasemiddleactualconfigurationfor准,toatdatabasemiddlenothaveservicequotienttype,usetemplate补充
     * support多same provider_code configuration(organizationadministratorhand动add)
     * finalresulthandleo clock,官方organizationwillfilter掉Delightfulservicequotient,普通organizationwillwillDelightfulservicequotient置top.
     * @param string $organizationCode organizationencoding
     * @param Category $category servicequotienttype
     * @return ProviderConfigDTO[]
     */
    public function getOrganizationProviders(string $organizationCode, Category $category, ?Status $status = null): array
    {
        // 1. getallquantityservicequotienttemplatecolumn表
        $templateProviders = $this->providerTemplateRepository->getAllProviderTemplates($category);

        // 2. getorganizationdownalreadyconfigurationservicequotient
        $organizationProviders = $this->getOrganizationProvidersFromDatabase($organizationCode, $category, $status);

        // 3. firstadddatabasemiddle所haveactualconfiguration(保留多same provider_code configuration)
        $result = $organizationProviders;

        // 4. check哪theseservicequotienttypeindatabasemiddlenothaveconfiguration,forthistheseaddtemplate
        $existingProviderCodes = [];
        foreach ($organizationProviders as $config) {
            if ($config->getProviderCode()) {
                $existingProviderCodes[] = $config->getProviderCode();
            }
        }

        // fordatabasemiddlenot存inservicequotienttypeaddtemplateconfiguration
        foreach ($templateProviders as $template) {
            if (! $template->getProviderCode() || ! in_array($template->getProviderCode(), $existingProviderCodes, true)) {
                $result[] = $template;
            }
        }
        // 5. finalresulthandle:sortandfilter
        $isOfficialOrganization = OfficialOrganizationUtil::isOfficialOrganization($organizationCode);
        $delightfulProvider = null;
        $otherProviders = [];

        foreach ($result as $provider) {
            if (! $provider->getProviderCode()) {
                continue;
            }

            // ifis官方organization,filter掉 Delightful servicequotient(Official),因for delightful servicequotientthenis官方organizationconfigurationmodel总and
            /*if ($isOfficialOrganization && $provider->getProviderCode() === ProviderCode::Official) {
                continue;
            }*/

            if ($provider->getProviderCode() === ProviderCode::Official) {
                $delightfulProvider = $provider;
            } else {
                $otherProviders[] = $provider;
            }
        }

        // tootherservicequotient按 sort fieldsort(numbermorebigmore靠front)
        usort($otherProviders, function ($a, $b) {
            if ($a->getSort() === $b->getSort()) {
                return strcmp($a->getId(), $b->getId()); // same sort valueo clock按 ID sort
            }
            return $b->getSort() <=> $a->getSort(); // 降序rowcolumn,numberbiginfront
        });

        // if找to Delightful servicequotient,willits放intheone位(non官方organization才willhave Delightful servicequotient)
        if ($delightfulProvider !== null) {
            $result = array_merge([$delightfulProvider], $otherProviders);
        } else {
            $result = $otherProviders;
        }

        return $result;
    }

    public function encryptionConfig(array $config, string $salt): string
    {
        return AesUtil::encode($this->_getAesKey($salt), Json::encode($config));
    }

    public function insert(ProviderConfigEntity $serviceProviderConfigEntity): ProviderConfigEntity
    {
        $attributes = $serviceProviderConfigEntity->toArray();
        $this->initializeEntityForCreation($serviceProviderConfigEntity, $attributes);

        $attributes['config'] = $this->encryptionConfig($attributes['config'], (string) $attributes['id']);
        $attributes['translate'] = Json::encode($serviceProviderConfigEntity->getTranslate());
        ProviderConfigModel::query()->create($attributes);
        return $serviceProviderConfigEntity;
    }

    /**
     * passconfigurationIDandorganizationencodinggetservicequotientconfiguration实body.
     */
    public function getProviderConfigEntityById(string $serviceProviderConfigId, string $organizationCode): ?ProviderConfigEntity
    {
        $configQuery = $this->createConfigQuery()
            ->where('id', $serviceProviderConfigId)
            ->where('organization_code', $organizationCode);

        $configResult = Db::select($configQuery->toSql(), $configQuery->getBindings());

        if (empty($configResult)) {
            return null;
        }

        return ProviderConfigAssembler::toEntity($configResult[0]);
    }

    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderConfigEntity
    {
        $builder = $this->createConfigQuery();
        $builder->where('id', $id);

        $result = Db::select($builder->toSql(), $builder->getBindings());

        if (empty($result)) {
            return null;
        }

        return ProviderConfigAssembler::toEntity($result[0]);
    }

    public function getByIdsWithoutOrganizationFilter(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $builder = $this->createConfigQuery();
        $ids = array_values(array_unique($ids));
        $builder->whereIn('id', $ids);
        $result = Db::select($builder->toSql(), $builder->getBindings());

        $entities = [];
        foreach ($result as $model) {
            $entities[$model['id']] = ProviderConfigAssembler::toEntity($model);
        }

        return $entities;
    }

    public function getAllByOrganization(ProviderDataIsolation $dataIsolation): array
    {
        $builder = $this->createConfigQuery()
            ->where('organization_code', $dataIsolation->getCurrentOrganizationCode())
            ->where('status', 1);

        $result = Db::select($builder->toSql(), $builder->getBindings());

        $entities = [];
        foreach ($result as $model) {
            $entities[] = ProviderConfigAssembler::toEntity($model);
        }

        return $entities;
    }

    /**
     * 准备移except软删相closefeature,temporarythis样写.create带have软deletefilter ProviderConfigModel querybuild器.
     */
    protected function createConfigQuery(): Builder
    {
        /* @phpstan-ignore-next-line */
        return ProviderConfigModel::query()->whereNull('deleted_at');
    }

    /**
     * create带have软deletefilter ProviderModel querybuild器.
     */
    private function createProviderQuery(): Builder
    {
        /* @phpstan-ignore-next-line */
        return ProviderModel::query()->whereNull('deleted_at');
    }

    /**
     * fromdatabasegetorganizationdownalreadyconfigurationservicequotient.
     * @return ProviderConfigDTO[]
     */
    private function getOrganizationProvidersFromDatabase(string $organizationCode, Category $category, ?Status $status = null): array
    {
        // according tocategorygetservicequotientIDcolumn表
        $serviceProviderIds = $this->getServiceProviderIdsByCategory($category);

        if (empty($serviceProviderIds)) {
            return [];
        }

        // according toorganizationencodingandservicequotientIDcolumn表getconfiguration
        $providerConfigQuery = $this->createConfigQuery()
            ->where('organization_code', $organizationCode)
            ->whereIn('service_provider_id', $serviceProviderIds)
            ->orderBy('sort', 'DESC')
            ->orderBy('id', 'ASC');

        if ($status) {
            $providerConfigQuery->where('status', $status->value);
        }

        $providerConfigsResult = Db::select($providerConfigQuery->toSql(), $providerConfigQuery->getBindings());

        // batchquantityqueryto应 provider info
        $providerMap = $this->getProviderMapByConfigs($providerConfigsResult);

        return ProviderConfigAssembler::toDTOListWithProviders($providerConfigsResult, $providerMap);
    }

    /**
     * according toconfigurationdatabatchquantityqueryto应 provider info.
     * @param array $configsResult configurationqueryresult
     * @return array provider ID to provider arraymapping
     */
    private function getProviderMapByConfigs(array $configsResult): array
    {
        if (empty($configsResult)) {
            return [];
        }

        // extract所have service_provider_id
        $providerIds = [];
        foreach ($configsResult as $config) {
            $providerIds[] = $config['service_provider_id'];
        }
        $providerIds = array_unique($providerIds);

        if (empty($providerIds)) {
            return [];
        }

        // batchquantityquery provider info
        $providerQuery = $this->createProviderQuery()
            ->whereIn('id', $providerIds);
        $providersResult = Db::select($providerQuery->toSql(), $providerQuery->getBindings());

        // create provider ID to provider datamapping
        $providerMap = [];
        foreach ($providersResult as $provider) {
            $providerMap[$provider['id']] = $provider;
        }

        return $providerMap;
    }

    /**
     * according tocategorygetservicequotientIDcolumn表.
     *
     * @param Category $category servicequotientcategory
     * @return array servicequotientIDarray
     */
    private function getServiceProviderIdsByCategory(Category $category): array
    {
        return $this->createProviderQuery()
            ->where('category', $category->value)
            ->pluck('id')
            ->toArray();
    }

    /**
     * aes keyadd盐.
     */
    private function _getAesKey(string $salt): string
    {
        return config('service_provider.model_aes_key') . $salt;
    }
}
