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

        // 添加sort（number越大越靠前）
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

        // 判断是create还是更new标志
        $isNewRecord = ! $providerConfigEntity->getId();

        if ($isNewRecord) {
            // create新record - 先generateID
            $this->initializeEntityForCreation($providerConfigEntity, $attributes);
        } else {
            // update现有record
            $now = new DateTime();
            $providerConfigEntity->setUpdatedAt($now);
            $attributes['updated_at'] = $now->format('Y-m-d H:i:s');

            // update操作时，移除不should被更newfield
            unset($attributes['id'], $attributes['created_at']);
        }

        // 对configurationdata进行encrypt（如果存在且未encrypt）
        if (! empty($attributes['config'])) {
            $configId = (string) $providerConfigEntity->getId();

            // 如果 config 是string且是valid的 JSON format（未encrypt的configurationdata），则needencrypt
            if (is_string($attributes['config']) && json_validate($attributes['config'])) {
                $decodedConfig = Json::decode($attributes['config']);
                $attributes['config'] = ProviderConfigAssembler::encodeConfig($decodedConfig, $configId);
            }
        }

        if ($isNewRecord) {
            // create新record
            ProviderConfigModel::query()->insert($attributes);
        } else {
            // update现有record
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
     * according toorganization和service商typegetservice商configuration列表.
     * 新逻辑：以database中的actualconfiguration为准，对于database中没有的service商type，usetemplate补充
     * support多个same provider_code 的configuration（organization管理员手动添加的）
     * finalresulthandle时，官方organizationwillfilter掉Delightfulservice商，普通organizationwill将Delightfulservice商置顶.
     * @param string $organizationCode organizationencoding
     * @param Category $category service商type
     * @return ProviderConfigDTO[]
     */
    public function getOrganizationProviders(string $organizationCode, Category $category, ?Status $status = null): array
    {
        // 1. get全量的service商template列表
        $templateProviders = $this->providerTemplateRepository->getAllProviderTemplates($category);

        // 2. getorganization下已configuration的service商
        $organizationProviders = $this->getOrganizationProvidersFromDatabase($organizationCode, $category, $status);

        // 3. 首先添加database中的所有actualconfiguration（保留多个same provider_code 的configuration）
        $result = $organizationProviders;

        // 4. check哪些service商type在database中没有configuration，为这些添加template
        $existingProviderCodes = [];
        foreach ($organizationProviders as $config) {
            if ($config->getProviderCode()) {
                $existingProviderCodes[] = $config->getProviderCode();
            }
        }

        // 为database中不存在的service商type添加templateconfiguration
        foreach ($templateProviders as $template) {
            if (! $template->getProviderCode() || ! in_array($template->getProviderCode(), $existingProviderCodes, true)) {
                $result[] = $template;
            }
        }
        // 5. finalresulthandle：sort和filter
        $isOfficialOrganization = OfficialOrganizationUtil::isOfficialOrganization($organizationCode);
        $delightfulProvider = null;
        $otherProviders = [];

        foreach ($result as $provider) {
            if (! $provider->getProviderCode()) {
                continue;
            }

            // 如果是官方organization，filter掉 Delightful service商（Official），因为 delightful service商就是官方organizationconfiguration的model总和
            /*if ($isOfficialOrganization && $provider->getProviderCode() === ProviderCode::Official) {
                continue;
            }*/

            if ($provider->getProviderCode() === ProviderCode::Official) {
                $delightfulProvider = $provider;
            } else {
                $otherProviders[] = $provider;
            }
        }

        // 对其他service商按 sort fieldsort（number越大越靠前）
        usort($otherProviders, function ($a, $b) {
            if ($a->getSort() === $b->getSort()) {
                return strcmp($a->getId(), $b->getId()); // same sort value时按 ID sort
            }
            return $b->getSort() <=> $a->getSort(); // 降序排列，number大的在前
        });

        // 如果找到 Delightful service商，将其放在第一位（非官方organization才will有 Delightful service商）
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
     * passconfigurationID和organizationencodinggetservice商configuration实体.
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
     * 准备移除软删相关feature，temporary这样写。create带有软deletefilter的 ProviderConfigModel querybuild器.
     */
    protected function createConfigQuery(): Builder
    {
        /* @phpstan-ignore-next-line */
        return ProviderConfigModel::query()->whereNull('deleted_at');
    }

    /**
     * create带有软deletefilter的 ProviderModel querybuild器.
     */
    private function createProviderQuery(): Builder
    {
        /* @phpstan-ignore-next-line */
        return ProviderModel::query()->whereNull('deleted_at');
    }

    /**
     * 从databasegetorganization下已configuration的service商.
     * @return ProviderConfigDTO[]
     */
    private function getOrganizationProvidersFromDatabase(string $organizationCode, Category $category, ?Status $status = null): array
    {
        // according tocategorygetservice商ID列表
        $serviceProviderIds = $this->getServiceProviderIdsByCategory($category);

        if (empty($serviceProviderIds)) {
            return [];
        }

        // according toorganizationencoding和service商ID列表getconfiguration
        $providerConfigQuery = $this->createConfigQuery()
            ->where('organization_code', $organizationCode)
            ->whereIn('service_provider_id', $serviceProviderIds)
            ->orderBy('sort', 'DESC')
            ->orderBy('id', 'ASC');

        if ($status) {
            $providerConfigQuery->where('status', $status->value);
        }

        $providerConfigsResult = Db::select($providerConfigQuery->toSql(), $providerConfigQuery->getBindings());

        // 批量query对应的 provider info
        $providerMap = $this->getProviderMapByConfigs($providerConfigsResult);

        return ProviderConfigAssembler::toDTOListWithProviders($providerConfigsResult, $providerMap);
    }

    /**
     * according toconfigurationdata批量query对应的 provider info.
     * @param array $configsResult configurationqueryresult
     * @return array provider ID 到 provider array的mapping
     */
    private function getProviderMapByConfigs(array $configsResult): array
    {
        if (empty($configsResult)) {
            return [];
        }

        // 提取所有的 service_provider_id
        $providerIds = [];
        foreach ($configsResult as $config) {
            $providerIds[] = $config['service_provider_id'];
        }
        $providerIds = array_unique($providerIds);

        if (empty($providerIds)) {
            return [];
        }

        // 批量query provider info
        $providerQuery = $this->createProviderQuery()
            ->whereIn('id', $providerIds);
        $providersResult = Db::select($providerQuery->toSql(), $providerQuery->getBindings());

        // create provider ID 到 provider data的mapping
        $providerMap = [];
        foreach ($providersResult as $provider) {
            $providerMap[$provider['id']] = $provider;
        }

        return $providerMap;
    }

    /**
     * according tocategorygetservice商ID列表.
     *
     * @param Category $category service商category
     * @return array service商IDarray
     */
    private function getServiceProviderIdsByCategory(Category $category): array
    {
        return $this->createProviderQuery()
            ->where('category', $category->value)
            ->pluck('id')
            ->toArray();
    }

    /**
     * aes key加盐.
     */
    private function _getAesKey(string $salt): string
    {
        return config('service_provider.model_aes_key') . $salt;
    }
}
