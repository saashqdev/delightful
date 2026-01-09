<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service;

use App\Domain\Provider\Entity\AiAbilityEntity;
use App\Domain\Provider\Entity\ValueObject\AiAbilityCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Query\AiAbilityQuery;
use App\Domain\Provider\Repository\Facade\AiAbilityRepositoryInterface;
use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Exception;
use Hyperf\Contract\ConfigInterface;

/**
 * AI 能力领域service.
 */
class AiAbilityDomainService
{
    public function __construct(
        private AiAbilityRepositoryInterface $aiAbilityRepository,
        private ConfigInterface $config,
    ) {
    }

    /**
     * according to能力codegetAI能力实体（用于运行时，不校验organization）.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离info
     * @param AiAbilityCode $code 能力code
     * @return AiAbilityEntity AI能力实体
     * @throws Exception 当能力不存在或未启用时throwexception
     */
    public function getByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code): AiAbilityEntity
    {
        $entity = $this->aiAbilityRepository->getByCode($dataIsolation, $code);

        if ($entity === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::AI_ABILITY_NOT_FOUND);
        }

        return $entity;
    }

    /**
     * get所有AI能力list（无pagination）.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离info
     * @return array<AiAbilityEntity> AI能力实体list
     */
    public function getAll(ProviderDataIsolation $dataIsolation): array
    {
        $query = new AiAbilityQuery();
        $page = Page::createNoPage();
        $result = $this->aiAbilityRepository->queries($dataIsolation, $query, $page);
        return $result['list'];
    }

    /**
     * paginationqueryAI能力list.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离info
     * @param AiAbilityQuery $query query条件
     * @param Page $page paginationinfo
     * @return array{total: int, list: array<AiAbilityEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, AiAbilityQuery $query, Page $page): array
    {
        return $this->aiAbilityRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * updateAI能力.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离info
     * @param AiAbilityCode $code 能力code
     * @param array $data updatedata
     * @return bool 是否updatesuccess
     * @throws Exception 当能力不存在时throwexception
     */
    public function updateByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code, array $data): bool
    {
        // check能力是否存在
        $entity = $this->aiAbilityRepository->getByCode($dataIsolation, $code);
        if ($entity === null) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::AI_ABILITY_NOT_FOUND);
        }

        if (empty($data)) {
            return true;
        }

        return $this->aiAbilityRepository->updateByCode($dataIsolation, $code, $data);
    }

    /**
     * initializeAI能力data.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离info
     * @return int initialize的quantity
     */
    public function initializeAbilities(ProviderDataIsolation $dataIsolation): int
    {
        $abilities = $this->config->get('ai_abilities.abilities', []);
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();
        $count = 0;

        foreach ($abilities as $abilityConfig) {
            // checkdatabase中是否已存在
            $code = AiAbilityCode::from($abilityConfig['code']);
            $existingEntity = $this->aiAbilityRepository->getByCode($dataIsolation, $code);

            // buildname和description（ensure是多语言format）
            $name = $abilityConfig['name'];
            if (is_string($name)) {
                $name = [
                    'zh_CN' => $name,
                    'en_US' => $name,
                ];
            }

            $description = $abilityConfig['description'];
            if (is_string($description)) {
                $description = [
                    'zh_CN' => $description,
                    'en_US' => $description,
                ];
            }

            if ($existingEntity === null) {
                // 不存在则create
                $entity = new AiAbilityEntity();
                $entity->setCode($abilityConfig['code']);
                $entity->setOrganizationCode($organizationCode);
                $entity->setName($name);
                $entity->setDescription($description);
                $entity->setIcon($abilityConfig['icon'] ?? '');
                $entity->setSortOrder($abilityConfig['sort_order'] ?? 0);
                $entity->setStatus($abilityConfig['status'] ?? true);
                $entity->setConfig($abilityConfig['config'] ?? []);

                $this->aiAbilityRepository->save($entity);
                ++$count;
            }
        }

        return $count;
    }
}
