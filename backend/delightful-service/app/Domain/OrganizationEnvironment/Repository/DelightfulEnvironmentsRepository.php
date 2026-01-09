<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Repository;

use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;
use App\Domain\OrganizationEnvironment\Repository\Facade\EnvironmentRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Model\DelightfulEnvironmentModel;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\Codec\Json;

readonly class DelightfulEnvironmentsRepository implements EnvironmentRepositoryInterface
{
    public function __construct(private DelightfulEnvironmentModel $delightfulEnvironmentModel)
    {
    }

    public function getEnvById(string $id): ?DelightfulEnvironmentEntity
    {
        $env = $this->getEnvByIdArray($id);
        if (! $env) {
            return null;
        }
        return new DelightfulEnvironmentEntity($env);
    }

    /**
     * @return DelightfulEnvironmentEntity[]
     */
    public function getDelightfulEnvironments(): array
    {
        $entities = [];
        foreach ($this->delightfulEnvironmentModel->newQuery()->get()->toArray() as $env) {
            $entities[] = new DelightfulEnvironmentEntity($env);
        }
        return $entities;
    }

    /**
     * @return DelightfulEnvironmentEntity[]
     */
    public function getDelightfulEnvironmentsByIds(array $ids): array
    {
        $entities = [];
        $data = $this->delightfulEnvironmentModel->newQuery()->whereIn('id', $ids)->get()->toArray();
        foreach ($data as $env) {
            $entities[] = new DelightfulEnvironmentEntity($env);
        }
        return $entities;
    }

    public function getDelightfulEnvironmentById(int $envId): ?DelightfulEnvironmentEntity
    {
        $delightfulOrganizationEnv = $this->delightfulEnvironmentModel->newQuery()
            ->where('id', $envId)
            ->first()
            ?->toArray();
        if (empty($delightfulOrganizationEnv)) {
            return null;
        }
        return new DelightfulEnvironmentEntity($delightfulOrganizationEnv);
    }

    // createenvironment
    public function createDelightfulEnvironment(DelightfulEnvironmentEntity $environmentDTO): DelightfulEnvironmentEntity
    {
        if (empty($environmentDTO->getId())) {
            $environmentDTO->setId($this->delightfulEnvironmentModel->newQuery()->max('id') + 1);
        }
        $time = date('Y-m-d H:i:s');
        $environmentDTO->setCreatedAt($time);
        $environmentDTO->setUpdatedAt($time);
        $envData = $environmentDTO->toArray();
        $extra = $environmentDTO->getExtra();
        if ($extra !== null) {
            $envData['extra'] = Json::encode($extra->toArray());
        }
        $this->delightfulEnvironmentModel->newQuery()->create($envData);
        return $environmentDTO;
    }

    // updateenvironment
    #[CacheEvict(prefix: 'delightful_environment', value: '_#{environmentDTO.delightfulId}')]
    public function updateDelightfulEnvironment(DelightfulEnvironmentEntity $environmentDTO): DelightfulEnvironmentEntity
    {
        $time = date('Y-m-d H:i:s');
        $environmentDTO->setUpdatedAt($time);
        $this->delightfulEnvironmentModel->newQuery()->where('id', $environmentDTO->getId())->update(
            [
                'deployment' => $environmentDTO->getDeployment(),
                'environment' => $environmentDTO->getEnvironment(),
                'environment_code' => $environmentDTO->getEnvironmentCode(),
                'open_platform_config' => Json::encode($environmentDTO->getOpenPlatformConfig()?->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'private_config' => Json::encode($environmentDTO->getPrivateConfig(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updated_at' => $environmentDTO->getUpdatedAt(),
                'extra' => Json::encode($environmentDTO->getExtra()?->toArray()),
            ]
        );
        return $environmentDTO;
    }

    public function getEnvironmentEntityByLoginCode(string $loginCode): ?DelightfulEnvironmentEntity
    {
        $delightfulOrganizationEnv = $this->delightfulEnvironmentModel->newQuery()
            ->where('environment_code', $loginCode)
            ->first();
        if (empty($delightfulOrganizationEnv)) {
            return null;
        }
        return new DelightfulEnvironmentEntity($delightfulOrganizationEnv->toArray());
    }

    #[Cacheable(prefix: 'delightful_environment', value: '_#{id}', ttl: 60)]
    private function getEnvByIdArray(string $id): ?array
    {
        $env = $this->delightfulEnvironmentModel::query()->find($id)?->toArray();
        if (! $env) {
            return null;
        }
        return $env;
    }
}
