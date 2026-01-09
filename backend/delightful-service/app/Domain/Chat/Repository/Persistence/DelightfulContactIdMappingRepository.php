<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Persistence;

use App\Domain\Chat\Entity\ValueObject\PlatformRootDepartmentId;
use App\Domain\Chat\Repository\Facade\DelightfulContactIdMappingRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulContactThirdPlatformIdMappingModel;
use App\Domain\Contact\Entity\DelightfulThirdPlatformIdMappingEntity;
use App\Domain\Contact\Entity\ValueObject\PlatformType;
use App\Domain\Contact\Entity\ValueObject\ThirdPlatformIdMappingType;
use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Hyperf\DbConnection\Db;

class DelightfulContactIdMappingRepository implements DelightfulContactIdMappingRepositoryInterface
{
    public function __construct(
        protected DelightfulContactThirdPlatformIdMappingModel $delightfulContactIdMappingModel
    ) {
    }

    /**
     * getthethreesideplatformdepartmentIDmappingclosesystem.
     *
     * @param string[] $thirdDepartmentIds
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function getThirdDepartmentIdsMapping(
        DelightfulEnvironmentEntity $delightfulEnvironmentEntity,
        array $thirdDepartmentIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array {
        $relationEnvIds = $this->getEnvRelationIds($delightfulEnvironmentEntity);
        $data = $this->delightfulContactIdMappingModel::query();

        // maintainoriginalhavequeryfieldorder
        // according toenvironmentIDquantitychoosesuitablequerymethod
        if (count($relationEnvIds) === 1) {
            $data->where('delightful_environment_id', reset($relationEnvIds));
        } else {
            $data->whereIn('delightful_environment_id', $relationEnvIds);
        }

        if (count($thirdDepartmentIds) > 0) {
            $data->whereIn('origin_id', $thirdDepartmentIds);
        }

        $data->where('mapping_type', ThirdPlatformIdMappingType::Department->value)
            ->where('third_platform_type', $thirdPlatformType->value)
            ->where('delightful_organization_code', $delightfulOrganizationCode);

        $data = Db::select($data->toSql(), $data->getBindings());
        $thirdPlatformIdMappingEntities = [];
        foreach ($data as $item) {
            $thirdPlatformIdMappingEntities[] = new DelightfulThirdPlatformIdMappingEntity($item);
        }
        return $thirdPlatformIdMappingEntities;
    }

    /**
     * getDelightfuldepartmentIDmappingclosesystem.
     *
     * @param string[] $delightfulDepartmentIds
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function getDelightfulDepartmentIdsMapping(
        array $delightfulDepartmentIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array {
        $data = $this->delightfulContactIdMappingModel::query()
            ->whereIn('new_id', $delightfulDepartmentIds)
            ->where('mapping_type', ThirdPlatformIdMappingType::Department->value)
            ->where('delightful_organization_code', $delightfulOrganizationCode)
            ->where('third_platform_type', $thirdPlatformType->value);
        $data = Db::select($data->toSql(), $data->getBindings());
        $thirdPlatformIdMappingEntities = [];
        foreach ($data as $item) {
            $thirdPlatformIdMappingEntities[] = new DelightfulThirdPlatformIdMappingEntity($item);
        }
        return $thirdPlatformIdMappingEntities;
    }

    /**
     * getthethreesideplatformuserIDmappingclosesystem.
     *
     * @param string[] $thirdUserIds
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function getThirdUserIdsMapping(
        DelightfulEnvironmentEntity $delightfulEnvironmentEntity,
        array $thirdUserIds,
        ?string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array {
        $relationEnvIds = $this->getEnvRelationIds($delightfulEnvironmentEntity);
        $query = $this->delightfulContactIdMappingModel::query();

        // maintainoriginalhavequeryfieldorder
        // according toenvironmentIDquantitychoosesuitablequerymethod
        if (count($relationEnvIds) === 1) {
            $query->where('delightful_environment_id', reset($relationEnvIds));
        } else {
            $query->whereIn('delightful_environment_id', $relationEnvIds);
        }

        $query->whereIn('origin_id', $thirdUserIds)
            ->where('mapping_type', ThirdPlatformIdMappingType::User->value);

        // havetheseplatformmultipleorganizationuser id oneto(such asdaybook),thereforequeryo clocknotwithorganizationencoding
        $delightfulOrganizationCode && $query->where('delightful_organization_code', $delightfulOrganizationCode);
        $thirdPlatformIdMappingEntities = [];
        $data = $query->where('third_platform_type', $thirdPlatformType->value);
        $data = Db::select($data->toSql(), $data->getBindings());
        foreach ($data as $item) {
            $thirdPlatformIdMappingEntities[] = new DelightfulThirdPlatformIdMappingEntity($item);
        }
        return $thirdPlatformIdMappingEntities;
    }

    /**
     * getMagicplatformuserIDmappingclosesystem.
     *
     * @param string[] $delightfulIds
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function getDelightfulIdsMapping(
        array $delightfulIds,
        ?string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType
    ): array {
        $query = $this->delightfulContactIdMappingModel::query()
            ->whereIn('new_id', $delightfulIds)
            ->where('mapping_type', ThirdPlatformIdMappingType::User->value);
        // havetheseplatformmultipleorganizationuser id oneto(such asdaybook),thereforequeryo clocknotwithorganizationencoding
        if ($thirdPlatformType !== PlatformType::Teamshare) {
            $delightfulOrganizationCode && $query->where('delightful_organization_code', $delightfulOrganizationCode);
        }
        $thirdPlatformIdMappingEntities = [];
        $data = $query->where('third_platform_type', $thirdPlatformType->value);
        $data = Db::select($data->toSql(), $data->getBindings());
        foreach ($data as $item) {
            $thirdPlatformIdMappingEntities[] = new DelightfulThirdPlatformIdMappingEntity($item);
        }
        return $thirdPlatformIdMappingEntities;
    }

    /**
     * @param DelightfulThirdPlatformIdMappingEntity[] $thirdPlatformIdMappingEntities
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function createThirdPlatformIdsMapping(array $thirdPlatformIdMappingEntities): array
    {
        $thirdPlatformIdMappings = [];
        $time = date('Y-m-d H:i:s');
        foreach ($thirdPlatformIdMappingEntities as $delightfulThirdPlatformIdMappingEntity) {
            if (empty($delightfulThirdPlatformIdMappingEntity->getDelightfulEnvironmentId())) {
                ExceptionBuilder::throw(ChatErrorCode::Delightful_ENVIRONMENT_NOT_FOUND);
            }
            if (empty($delightfulThirdPlatformIdMappingEntity->getNewId())) {
                $newId = (string) IdGenerator::getSnowId();
            } else {
                $newId = $delightfulThirdPlatformIdMappingEntity->getNewId();
            }
            if (empty($delightfulThirdPlatformIdMappingEntity->getId())) {
                $id = (string) IdGenerator::getSnowId();
            } else {
                $id = $delightfulThirdPlatformIdMappingEntity->getId();
            }
            $delightfulThirdPlatformIdMappingEntity->setNewId($newId);
            $delightfulThirdPlatformIdMappingEntity->setId($id);
            $delightfulThirdPlatformIdMappingEntity->setCreatedAt($time);
            $delightfulThirdPlatformIdMappingEntity->setUpdatedAt($time);
            $thirdPlatformIdMappings[] = [
                'id' => $id, // temporaryo clockprimary key idsetforandnew_idsamevalue,bybackhaveneedcansplitminute
                'delightful_organization_code' => $delightfulThirdPlatformIdMappingEntity->getDelightfulOrganizationCode(),
                'mapping_type' => $delightfulThirdPlatformIdMappingEntity->getMappingType(),
                'third_platform_type' => $delightfulThirdPlatformIdMappingEntity->getThirdPlatformType(),
                'origin_id' => $delightfulThirdPlatformIdMappingEntity->getOriginId(),
                'new_id' => $newId,
                'delightful_environment_id' => $delightfulThirdPlatformIdMappingEntity->getDelightfulEnvironmentId(),
                'created_at' => $time,
                'updated_at' => $time,
                'deleted_at' => null,
            ];
        }
        $this->delightfulContactIdMappingModel::query()->insert($thirdPlatformIdMappings);
        return $thirdPlatformIdMappingEntities;
    }

    public function getDepartmentRootId(string $delightfulOrganizationCode, PlatformType $platformType): string
    {
        return $this->delightfulContactIdMappingModel::query()
            ->where('delightful_organization_code', $delightfulOrganizationCode)
            ->where('mapping_type', ThirdPlatformIdMappingType::Department->value)
            ->where('third_platform_type', $platformType->value)
            ->where('origin_id', PlatformRootDepartmentId::Delightful)
            ->value('new_id');
    }

    public function updateMappingEnvId(int $envId): int
    {
        return $this->delightfulContactIdMappingModel::query()
            ->where('delightful_environment_id', 0)
            ->update(['delightful_environment_id' => $envId]);
    }

    public function deleteThirdPlatformIdsMapping(
        array $originIds,
        string $delightfulOrganizationCode,
        PlatformType $thirdPlatformType,
        ThirdPlatformIdMappingType $mappingType
    ): int {
        if (empty($originIds)) {
            return 0;
        }
        return (int) $this->delightfulContactIdMappingModel::query()
            ->whereIn('origin_id', $originIds)
            ->where('delightful_organization_code', $delightfulOrganizationCode)
            ->where('third_platform_type', $thirdPlatformType->value)
            ->where('mapping_type', $mappingType->value)
            ->delete();
    }

    /**
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    public function getThirdDepartments(array $currentDepartmentIds, string $delightfulOrganizationCode, PlatformType $thirdPlatformType): array
    {
        $mappingArrays = $this->delightfulContactIdMappingModel::query()
            ->whereIn('new_id', $currentDepartmentIds)
            ->where('mapping_type', ThirdPlatformIdMappingType::Department->value)
            ->where('delightful_organization_code', $delightfulOrganizationCode)
            ->where('third_platform_type', $thirdPlatformType->value);
        $mappingArrays = Db::select($mappingArrays->toSql(), $mappingArrays->getBindings());
        return $this->convertToEntities($mappingArrays);
    }

    /**
     * prepublishandproductioncanregard asisoneenvironment, bythiswithinprocessonedownassociateenvironment ids.
     * */
    private function getEnvRelationIds(DelightfulEnvironmentEntity $delightfulEnvironmentEntity): array
    {
        $relationEnvIds = $delightfulEnvironmentEntity->getExtra()?->getRelationEnvIds();
        if (empty($relationEnvIds)) {
            $relationEnvIds = [$delightfulEnvironmentEntity->getId()];
        } else {
            $relationEnvIds[] = $delightfulEnvironmentEntity->getId();
            // toenvironmentIDconductgoreloadprocess
            $relationEnvIds = array_unique($relationEnvIds);
        }
        return $relationEnvIds;
    }

    /**
     * willarraydataconvertforactualbodyobject
     * @return DelightfulThirdPlatformIdMappingEntity[]
     */
    private function convertToEntities(array $dataArrays): array
    {
        $result = [];
        foreach ($dataArrays as $data) {
            $entity = new DelightfulThirdPlatformIdMappingEntity();
            $entity->setId($data['id']);
            $entity->setOriginId($data['origin_id']);
            $entity->setNewId($data['new_id']);
            $entity->setThirdPlatformType(PlatformType::from($data['third_platform_type']));
            $entity->setDelightfulOrganizationCode($data['delightful_organization_code']);
            $entity->setMappingType(ThirdPlatformIdMappingType::from($data['mapping_type']));
            $entity->setCreatedAt($data['created_at']);
            $entity->setUpdatedAt($data['updated_at']);
            $entity->setDeletedAt($data['deleted_at']);

            $result[] = $entity;
        }
        return $result;
    }
}
