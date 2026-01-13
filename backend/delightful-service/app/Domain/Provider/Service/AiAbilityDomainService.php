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
 * AI cancapabilitydomainservice.
 */
class AiAbilityDomainService
{
    public function __construct(
        private AiAbilityRepositoryInterface $aiAbilityRepository,
        private ConfigInterface $config,
    ) {
    }

    /**
     * according tocancapabilitycodegetAIcanimplementationbody(useatrunlineo clock,notvalidationorganization).
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityCode $code cancapabilitycode
     * @return AiAbilityEntity AIcanimplementationbody
     * @throws Exception whencancapabilitynotexistsinornotenableo clockthrowexception
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
     * get haveAIcancapabilitylist(nopagination).
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @return array<AiAbilityEntity> AIcanimplementationbodylist
     */
    public function getAll(ProviderDataIsolation $dataIsolation): array
    {
        $query = new AiAbilityQuery();
        $page = Page::createNoPage();
        $result = $this->aiAbilityRepository->queries($dataIsolation, $query, $page);
        return $result['list'];
    }

    /**
     * paginationqueryAIcancapabilitylist.
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
     * updateAIcancapability.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationinfo
     * @param AiAbilityCode $code cancapabilitycode
     * @param array $data updatedata
     * @return bool whetherupdatesuccess
     * @throws Exception whencancapabilitynotexistsino clockthrowexception
     */
    public function updateByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code, array $data): bool
    {
        // checkcancapabilitywhetherexistsin
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
     * initializeAIcancapabilitydata.
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
            // checkdatabasemiddlewhetheralreadyexistsin
            $code = AiAbilityCode::from($abilityConfig['code']);
            $existingEntity = $this->aiAbilityRepository->getByCode($dataIsolation, $code);

            // buildnameanddescription(ensureismultiplelanguageformat)
            $name = $abilityConfig['name'];
            if (is_string($name)) {
                $name = [
                    'en_US' => $name,
                    'en_US' => $name,
                ];
            }

            $description = $abilityConfig['description'];
            if (is_string($description)) {
                $description = [
                    'en_US' => $description,
                    'en_US' => $description,
                ];
            }

            if ($existingEntity === null) {
                // notexistsinthencreate
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
