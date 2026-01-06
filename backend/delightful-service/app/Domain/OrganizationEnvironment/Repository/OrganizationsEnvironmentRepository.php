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
    public function __construct(private DelightfulOrganizationsEnvironmentModel $magicEnvironments)
    {
    }

    public function getOrganizationEnvironmentByDelightfulOrganizationCode(string $magicOrganizationCode): ?DelightfulOrganizationEnvEntity
    {
        $magicOrganizationEnvData = $this->getOrganizationEnvironmentByDelightfulOrganizationCodeArray($magicOrganizationCode);
        if ($magicOrganizationEnvData === null) {
            return null;
        }
        return DelightfulEnvironmentAssembler::getDelightfulOrganizationEnvEntity($magicOrganizationEnvData);
    }

    public function getOrganizationEnvironmentByOrganizationCode(string $originOrganizationCode, DelightfulEnvironmentEntity $magicEnvironmentEntity): ?DelightfulOrganizationEnvEntity
    {
        $magicOrganizationEnv = $this->magicEnvironments->newQuery()
            ->whereIn('environment_id', $magicEnvironmentEntity->getRelationEnvIds())
            ->where('origin_organization_code', $originOrganizationCode)
            ->first();

        if ($magicOrganizationEnv === null) {
            return null;
        }
        return DelightfulEnvironmentAssembler::getDelightfulOrganizationEnvEntity($magicOrganizationEnv->toArray());
    }

    public function createOrganizationEnvironment(DelightfulOrganizationEnvEntity $magicOrganizationEnvEntity): void
    {
        if (empty($magicOrganizationEnvEntity->getId())) {
            $magicOrganizationEnvEntity->setId((string) IdGenerator::getSnowId());
        }
        $time = date('Y-m-d H:i:s');
        $magicOrganizationEnvEntity->setCreatedAt($time);
        $magicOrganizationEnvEntity->setUpdatedAt($time);
        $this->magicEnvironments->newQuery()->create($magicOrganizationEnvEntity->toArray());
    }

    /**
     * @param string[] $magicOrganizationCodes
     * @return DelightfulOrganizationEnvEntity[]
     */
    public function getOrganizationEnvironments(array $magicOrganizationCodes, DelightfulEnvironmentEntity $magicEnvironmentEntity): array
    {
        $magicOrganizationEnvironments = $this->magicEnvironments->newQuery()
            ->whereIn('magic_organization_code', $magicOrganizationCodes)
            ->whereIn('environment_id', $magicEnvironmentEntity->getRelationEnvIds())
            ->get()
            ->toArray();

        if (empty($magicOrganizationEnvironments)) {
            return [];
        }
        $magicOrganizationEnvEntities = [];
        foreach ($magicOrganizationEnvironments as $magicOrganizationEnvironment) {
            $magicOrganizationEnvEntities[] = DelightfulEnvironmentAssembler::getDelightfulOrganizationEnvEntity($magicOrganizationEnvironment);
        }
        return $magicOrganizationEnvEntities;
    }

    /**
     * 获取所有组织编码
     * @return string[]
     */
    public function getAllOrganizationCodes(): array
    {
        $query = $this->magicEnvironments->newQuery()->select('magic_organization_code');
        $result = Db::select($query->toSql(), $query->getBindings());
        return array_column($result, 'magic_organization_code');
    }

    public function getOrganizationEnvironmentByThirdPartyOrganizationCode(string $thirdPartyOrganizationCode, DelightfulEnvironmentEntity $magicEnvironmentEntity): ?DelightfulOrganizationEnvEntity
    {
        $magicOrganizationEnv = $this->magicEnvironments->newQuery()
            ->whereIn('environment_id', $magicEnvironmentEntity->getRelationEnvIds())
            ->where('origin_organization_code', $thirdPartyOrganizationCode)
            ->first();

        if ($magicOrganizationEnv === null) {
            return null;
        }
        return DelightfulEnvironmentAssembler::getDelightfulOrganizationEnvEntity($magicOrganizationEnv->toArray());
    }

    #[Cacheable(prefix: 'magic_organizations_environment', ttl: 60, value: '_#{magicOrganizationCode}')]
    private function getOrganizationEnvironmentByDelightfulOrganizationCodeArray(string $magicOrganizationCode): ?array
    {
        $magicOrganizationEnv = $this->magicEnvironments->newQuery()
            ->where('magic_organization_code', $magicOrganizationCode)
            ->first();

        if ($magicOrganizationEnv === null) {
            return null;
        }
        return $magicOrganizationEnv->toArray();
    }
}
