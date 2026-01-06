<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Service;

use App\Domain\OrganizationEnvironment\DTO\DelightfulOrganizationEnvDTO;
use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;
use App\Domain\OrganizationEnvironment\Entity\DelightfulOrganizationEnvEntity;
use App\Domain\OrganizationEnvironment\Entity\ValueObject\DeploymentEnum;
use App\Domain\OrganizationEnvironment\Repository\Facade\EnvironmentRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationsEnvironmentRepositoryInterface;
use App\Domain\Token\Entity\DelightfulTokenEntity;
use App\Domain\Token\Entity\ValueObject\DelightfulTokenType;
use App\Domain\Token\Repository\Facade\DelightfulTokenRepositoryInterface;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Hyperf\Codec\Json;
use Hyperf\Redis\Redis;

class DelightfulOrganizationEnvDomainService
{
    public function __construct(
        protected EnvironmentRepositoryInterface $delightfulEnvironmentsRepository,
        protected OrganizationsEnvironmentRepositoryInterface $delightfulOrganizationsEnvironmentRepository,
        protected LockerInterface $lock,
        protected DelightfulTokenRepositoryInterface $delightfulTokenRepository,
        protected Redis $redis
    ) {
    }

    public function getOrCreateOrganizationsEnvironment(string $originOrganizationCode, DelightfulEnvironmentEntity $delightfulEnvEntity): DelightfulOrganizationEnvEntity
    {
        // 加自旋锁防并发
        $spinLockKey = sprintf('getOrCreateOrganizationsEnvironment:envId:%s', $delightfulEnvEntity->getId());
        $owner = random_bytes(8);
        $this->lock->spinLock($spinLockKey, $owner);
        try {
            // 组织所在的环境
            $orgEnvEntity = $this->delightfulOrganizationsEnvironmentRepository->getOrganizationEnvironmentByOrganizationCode(
                $originOrganizationCode,
                $delightfulEnvEntity
            );
            if (! empty($orgEnvEntity)) {
                return $orgEnvEntity;
            }
            // 创建组织环境:如果是 saas 则不改变组织编码。
            if ($delightfulEnvEntity->getDeployment() === DeploymentEnum::SaaS) {
                $delightfulOrganizationCode = $originOrganizationCode;
            } else {
                $delightfulOrganizationCode = IdGenerator::getDelightfulOrganizationCode();
            }
            $orgEnvEntity = new DelightfulOrganizationEnvEntity(
                [
                    'delightful_organization_code' => $delightfulOrganizationCode,
                    'origin_organization_code' => $originOrganizationCode,
                    'environment_id' => $delightfulEnvEntity->getId(),
                    'login_code' => $delightfulEnvEntity->getId() . $originOrganizationCode,
                ]
            );
            $this->delightfulOrganizationsEnvironmentRepository->createOrganizationEnvironment($orgEnvEntity);
            return $orgEnvEntity;
        } finally {
            $this->lock->release($spinLockKey, $owner);
        }
    }

    public function getOrganizationEnvironmentByThirdPartyOrganizationCode(string $thirdPartyOrganizationCode, DelightfulEnvironmentEntity $delightfulEnvEntity): ?DelightfulOrganizationEnvEntity
    {
        return $this->delightfulOrganizationsEnvironmentRepository->getOrganizationEnvironmentByThirdPartyOrganizationCode(
            $thirdPartyOrganizationCode,
            $delightfulEnvEntity
        );
    }

    public function getDelightfulEnvironmentById(int $envId): ?DelightfulEnvironmentEntity
    {
        // 组织所在的环境
        return $this->delightfulEnvironmentsRepository->getEnvById((string) $envId);
    }

    public function getOrganizationsEnvironmentDTO(string $delightfulOrganizationCode): ?DelightfulOrganizationEnvDTO
    {
        // 组织所在的环境 id
        $organizationEnvEntity = $this->delightfulOrganizationsEnvironmentRepository->getOrganizationEnvironmentByDelightfulOrganizationCode(
            $delightfulOrganizationCode
        );
        if (! $organizationEnvEntity) {
            return null;
        }
        // 环境的详情
        $environmentEntity = $this->delightfulEnvironmentsRepository->getEnvById((string) $organizationEnvEntity->getEnvironmentId());
        if (! $environmentEntity) {
            return null;
        }
        $dto = new DelightfulOrganizationEnvDTO();
        $dto->setOrgEnvId($organizationEnvEntity->getId());
        $dto->setLoginCode($organizationEnvEntity->getLoginCode());
        $dto->setDelightfulOrganizationCode($organizationEnvEntity->getDelightfulOrganizationCode());
        $dto->setOriginOrganizationCode($organizationEnvEntity->getOriginOrganizationCode());
        $dto->setEnvironmentId($organizationEnvEntity->getEnvironmentId());
        $dto->setDeployment($environmentEntity->getDeployment());
        $dto->setEnvironment($environmentEntity->getEnvironment());
        $dto->setOpenPlatformConfig($environmentEntity->getOpenPlatformConfig());
        $dto->setCreatedAt($organizationEnvEntity->getCreatedAt());
        $dto->setUpdatedAt($organizationEnvEntity->getUpdatedAt());
        $dto->setExtra($environmentEntity->getExtra());
        $dto->setDelightfulEnvironmentEntity($environmentEntity);
        return $dto;
    }

    /**
     * @return DelightfulEnvironmentEntity[]
     */
    public function getEnvironmentEntities(): array
    {
        // 所有存在开放平台的环境
        return $this->delightfulEnvironmentsRepository->getDelightfulEnvironments();
    }

    /**
     * @return DelightfulEnvironmentEntity[]
     */
    public function getEnvironmentEntitiesByIds(array $ids): array
    {
        return $this->delightfulEnvironmentsRepository->getDelightfulEnvironmentsByIds($ids);
    }

    // 创建环境
    public function createEnvironment(DelightfulEnvironmentEntity $environmentDTO): DelightfulEnvironmentEntity
    {
        return $this->delightfulEnvironmentsRepository->createDelightfulEnvironment($environmentDTO);
    }

    // 更新环境
    public function updateEnvironment(DelightfulEnvironmentEntity $environmentDTO): DelightfulEnvironmentEntity
    {
        return $this->delightfulEnvironmentsRepository->updateDelightfulEnvironment($environmentDTO);
    }

    public function getEnvironmentEntityByLoginCode(string $loginCode): ?DelightfulEnvironmentEntity
    {
        return $this->delightfulEnvironmentsRepository->getEnvironmentEntityByLoginCode($loginCode);
    }

    public function getEnvironmentEntityByAuthorization(string $authorization): ?DelightfulEnvironmentEntity
    {
        $redisCacheKey = 'getEnvironmentEntityByAuthorization:' . md5($authorization);
        $data = $this->redis->get($redisCacheKey);
        if ($data) {
            return new DelightfulEnvironmentEntity(Json::decode($data));
        }
        // 查询 token 是否已经绑定 (调用了 delightful/auth/check)
        $tokenDTO = new DelightfulTokenEntity();
        $tokenDTO->setType(DelightfulTokenType::Account);
        $tokenDTO->setToken($tokenDTO->getDelightfulShortToken($authorization));
        $delightfulTokenEntity = $this->delightfulTokenRepository->getTokenEntity($tokenDTO);
        if (! $delightfulTokenEntity) {
            return null;
        }
        $envId = $delightfulTokenEntity->getExtra()?->getDelightfulEnvId();
        if (empty($envId)) {
            return null;
        }
        $delightfulEnvironmentEntity = $this->getDelightfulEnvironmentById($envId);
        if ($delightfulEnvironmentEntity === null) {
            return null;
        }
        $data = Json::encode($delightfulEnvironmentEntity->toArray());
        $this->redis->setex($redisCacheKey, 300, $data);
        return $delightfulEnvironmentEntity;
    }

    /**
     * 当前环境默认的 env 配置。 访问 saas 时允许前端不传环境 id，使用默认的环境配置。
     */
    public function getCurrentDefaultDelightfulEnv(): ?DelightfulEnvironmentEntity
    {
        $envId = env('DELIGHTFUL_ENV_ID');
        if (empty($envId)) {
            return null;
        }
        return $this->getDelightfulEnvironmentById((int) $envId);
    }

    public function getOrganizationEnvironmentByOriginOrganizationCode(string $originOrganizationCode, DelightfulEnvironmentEntity $delightfulEnvEntity): ?DelightfulOrganizationEnvEntity
    {
        return $this->delightfulOrganizationsEnvironmentRepository->getOrganizationEnvironmentByOrganizationCode(
            $originOrganizationCode,
            $delightfulEnvEntity
        );
    }

    public function getOrganizationEnvironmentByDelightfulOrganizationCode(string $delightfulOrganizationCode): ?DelightfulOrganizationEnvEntity
    {
        return $this->delightfulOrganizationsEnvironmentRepository->getOrganizationEnvironmentByDelightfulOrganizationCode(
            $delightfulOrganizationCode
        );
    }

    /**
     * 获取所有组织编码
     * @return string[]
     */
    public function getAllOrganizationCodes(): array
    {
        return $this->delightfulOrganizationsEnvironmentRepository->getAllOrganizationCodes();
    }
}
