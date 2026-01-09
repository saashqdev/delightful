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
 * AI can力领域service.
 */
class AiAbilityDomainService
{
    public function __construct(
        private AiAbilityRepositoryInterface $aiAbilityRepository,
        private ConfigInterface $config,
    ) {
    }

    /**
     * according tocan力codegetAIcan力实body(useat运lineo clock,notvalidationorganization).
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityCode $code can力code
     * @return AiAbilityEntity AIcan力实body
     * @throws Exception whencan力not存inornotenableo clockthrowexception
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
     * get所haveAIcan力list(nopagination).
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @return array<AiAbilityEntity> AIcan力实bodylist
     */
    public function getAll(ProviderDataIsolation $dataIsolation): array
    {
        $query = new AiAbilityQuery();
        $page = Page::createNoPage();
        $result = $this->aiAbilityRepository->queries($dataIsolation, $query, $page);
        return $result['list'];
    }

    /**
     * paginationqueryAIcan力list.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityQuery $query queryitemitem
     * @param Page $page paginationinfo
     * @return array{total: int, list: array<AiAbilityEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, AiAbilityQuery $query, Page $page): array
    {
        return $this->aiAbilityRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * updateAIcan力.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityCode $code can力code
     * @param array $data updatedata
     * @return bool whetherupdatesuccess
     * @throws Exception whencan力not存ino clockthrowexception
     */
    public function updateByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code, array $data): bool
    {
        // checkcan力whether存in
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
     * initializeAIcan力data.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @return int initializequantity
     */
    public function initializeAbilities(ProviderDataIsolation $dataIsolation): int
    {
        $abilities = $this->config->get('ai_abilities.abilities', []);
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();
        $count = 0;

        foreach ($abilities as $abilityConfig) {
            // checkdatabasemiddlewhetheralready存in
            $code = AiAbilityCode::from($abilityConfig['code']);
            $existingEntity = $this->aiAbilityRepository->getByCode($dataIsolation, $code);

            // buildnameanddescription(ensureis多languageformat)
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
                // not存inthencreate
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
