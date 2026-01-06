<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Repository;

use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;
use App\Domain\OrganizationEnvironment\Entity\DelightfulOrganizationEnvEntity;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationsEnvironmentRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Model\DelightfulOrganizationsEnvironmentModel;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Chat\Assembler\DelightfulEnvironmentAssembler;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\DbConnection\Db;

readonly class OrganizationsEnvironmentRepository implements OrganizationsEnvironmentRepositoryInterface
{
    public function __construct(private DelightfulOrganizationsEnvironmentModel $delightfulEnvironments)
    {
    }

    public function getOrganizationEnvironmentByDelightfulOrganizationCode(string $delightfulOrganizationCode): ?DelightfulOrganizationEnvEntity
    {
        $delightfulOrganizationEnvData = $this->getOrganizationEnvironmentByDelightfulOrganizationCodeArray($delightfulOrganizationCode);
        if ($delightfulOrganizationEnvData === null) {
            return null;
        }
        return DelightfulEnvironmentAssembler::getDelightfulOrganizationEnvEntity($delightfulOrganizationEnvData);
    }

    public function getOrganizationEnvironmentByOrganizationCode(string $originOrganizationCode, DelightfulEnvironmentEntity $delightfulEnvironmentEntity): ?DelightfulOrganizationEnvEntity
    {
        $delightfulOrganizationEnv = $this->delightfulEnvironments->newQuery()
            ->whereIn('environment_id', $delightfulEnvironmentEntity->getRelationEnvIds())
            ->where('origin_organization_code', $originOrganizationCode)
            ->first();

        if ($delightfulOrganizationEnv === null) {
            return null;
        }
        return DelightfulEnvironmentAssembler::getDelightfulOrganizationEnvEntity($delightfulOrganizationEnv->toArray());
    }

    public function createOrganizationEnvironment(DelightfulOrganizationEnvEntity $delightfulOrganizationEnvEntity): void
    {
        if (empty($delightfulOrganizationEnvEntity->getId())) {
            $delightfulOrganizationEnvEntity->setId((string) IdGenerator::getSnowId());
        }
        $time = date('Y-m-d H:i:s');
        $delightfulOrganizationEnvEntity->setCreatedAt($time);
        $delightfulOrganizationEnvEntity->setUpdatedAt($time);
        $this->delightfulEnvironments->newQuery()->create($delightfulOrganizationEnvEntity->toArray());
    }

    /**
     * @param string[] $delightfulOrganizationCodes
     * @return DelightfulOrganizationEnvEntity[]
     */
    public function getOrganizationEnvironments(array $delightfulOrganizationCodes, DelightfulEnvironmentEntity $delightfulEnvironmentEntity): array
    {
        $delightfulOrganizationEnvironments = $this->delightfulEnvironments->newQuery()
            ->whereIn('delightful_organization_code', $delightfulOrganizationCodes)
            ->whereIn('environment_id', $delightfulEnvironmentEntity->getRelationEnvIds())
            ->get()
            ->toArray();

        if (empty($delightfulOrganizationEnvironments)) {
            return [];
        }
        $delightfulOrganizationEnvEntities = [];
        foreach ($delightfulOrganizationEnvironments as $delightfulOrganizationEnvironment) {
            $delightfulOrganizationEnvEntities[] = DelightfulEnvironmentAssembler::getDelightfulOrganizationEnvEntity($delightfulOrganizationEnvironment);
        }
        return $delightfulOrganizationEnvEntities;
    }

    /**
     * 获取所有组织编码
     * @return string[]
     */
    public function getAllOrganizationCodes(): array
    {
        $query = $this->delightfulEnvironments->newQuery()->select('delightful_organization_code');
        $result = Db::select($query->toSql(), $query->getBindings());
        return array_column($result, 'delightful_organization_code');
    }

    public function getOrganizationEnvironmentByThirdPartyOrganizationCode(string $thirdPartyOrganizationCode, DelightfulEnvironmentEntity $delightfulEnvironmentEntity): ?DelightfulOrganizationEnvEntity
    {
        $delightfulOrganizationEnv = $this->delightfulEnvironments->newQuery()
            ->whereIn('environment_id', $delightfulEnvironmentEntity->getRelationEnvIds())
            ->where('origin_organization_code', $thirdPartyOrganizationCode)
            ->first();

        if ($delightfulOrganizationEnv === null) {
            return null;
        }
        return DelightfulEnvironmentAssembler::getDelightfulOrganizationEnvEntity($delightfulOrganizationEnv->toArray());
    }

    #[Cacheable(prefix: 'delightful_organizations_environment', ttl: 60, value: '_#{delightfulOrganizationCode}')]
    private function getOrganizationEnvironmentByDelightfulOrganizationCodeArray(string $delightfulOrganizationCode): ?array
    {
        $delightfulOrganizationEnv = $this->delightfulEnvironments->newQuery()
            ->where('delightful_organization_code', $delightfulOrganizationCode)
            ->first();

        if ($delightfulOrganizationEnv === null) {
            return null;
        }
        return $delightfulOrganizationEnv->toArray();
    }
}
