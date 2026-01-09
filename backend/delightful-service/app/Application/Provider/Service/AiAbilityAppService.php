<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Provider\Service;

use App\Application\Kernel\AbstractKernelAppService;
use App\Application\Provider\DTO\AiAbilityDetailDTO;
use App\Application\Provider\DTO\AiAbilityListDTO;
use App\Domain\Provider\Entity\ValueObject\AiAbilityCode;
use App\Domain\Provider\Entity\ValueObject\Query\AiAbilityQuery;
use App\Domain\Provider\Service\AiAbilityDomainService;
use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Provider\Assembler\AiAbilityAssembler;
use App\Interfaces\Provider\DTO\UpdateAiAbilityRequest;
use Hyperf\Contract\TranslatorInterface;
use Throwable;

/**
 * AIcan力applicationservice.
 */
class AiAbilityAppService extends AbstractKernelAppService
{
    public function __construct(
        private AiAbilityDomainService $aiAbilityDomainService,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * get所haveAIcan力list.
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     * @return array<AiAbilityListDTO>
     */
    public function queries(DelightfulUserAuthorization $authorization): array
    {
        $dataIsolation = $this->createProviderDataIsolation($authorization);

        $locale = $this->translator->getLocale();
        $query = new AiAbilityQuery();
        $result = $this->aiAbilityDomainService->queries($dataIsolation, $query, Page::createNoPage());

        return AiAbilityAssembler::entitiesToListDTOs($result['list'], $locale);
    }

    /**
     * getAIcan力detail.
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     * @param string $code can力code
     */
    public function getDetail(DelightfulUserAuthorization $authorization, string $code): AiAbilityDetailDTO
    {
        $dataIsolation = $this->createProviderDataIsolation($authorization);

        // verifycodewhethervalid
        try {
            $codeEnum = AiAbilityCode::from($code);
        } catch (Throwable $e) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::AI_ABILITY_NOT_FOUND);
        }

        // getcan力detail
        $entity = $this->aiAbilityDomainService->getByCode($dataIsolation, $codeEnum);

        $locale = $this->translator->getLocale();
        return AiAbilityAssembler::entityToDetailDTO($entity, $locale);
    }

    /**
     * updateAIcan力.
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     * @param UpdateAiAbilityRequest $request updaterequest
     * @return bool whetherupdatesuccess
     */
    public function update(DelightfulUserAuthorization $authorization, UpdateAiAbilityRequest $request): bool
    {
        $dataIsolation = $this->createProviderDataIsolation($authorization);

        // verifycodewhethervalid
        try {
            $code = AiAbilityCode::from($request->getCode());
        } catch (Throwable $e) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::AI_ABILITY_NOT_FOUND);
        }

        // buildupdatedata(supportchoosepropertyupdate)
        $updateData = [];
        if ($request->hasStatus()) {
            $updateData['status'] = $request->getStatus();
        }
        if ($request->hasConfig()) {
            // getcurrentdatabasemiddleconfiguration
            $entity = $this->aiAbilityDomainService->getByCode($dataIsolation, $code);
            $dbConfig = $entity->getConfig();

            // 智canmergeconfiguration(retainbe脱敏api_key)
            $mergedConfig = $this->mergeConfigPreservingApiKeys($dbConfig, $request->getConfig());
            $updateData['config'] = $mergedConfig;
        }

        // ifnothavewantupdatedata,directlyreturnsuccess
        if (empty($updateData)) {
            return true;
        }

        // pass DomainService update
        return $this->aiAbilityDomainService->updateByCode($dataIsolation, $code, $updateData);
    }

    /**
     * initializeAIcan力data(fromconfigurationfilesynctodatabase).
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     * @return int initializequantity
     */
    public function initializeAbilities(DelightfulUserAuthorization $authorization): int
    {
        $dataIsolation = $this->createProviderDataIsolation($authorization);

        return $this->aiAbilityDomainService->initializeAbilities($dataIsolation);
    }

    /**
     * 智canmergeconfiguration(retainbe脱敏api_keyoriginalvalue).
     *
     * @param array $dbConfig databaseoriginalconfiguration
     * @param array $frontendConfig front端传comeconfiguration(maybecontain脱敏api_key)
     * @return array mergebackconfiguration
     */
    private function mergeConfigPreservingApiKeys(array $dbConfig, array $frontendConfig): array
    {
        $result = [];

        // 遍历front端configuration所havefield
        foreach ($frontendConfig as $key => $value) {
            // ifis api_key fieldandcontain脱敏mark ***
            if ($key === 'api_key' && is_string($value) && str_contains($value, '*')) {
                // usedatabasemiddleoriginalvalue
                $result[$key] = $dbConfig[$key] ?? $value;
            }
            // ifisarray,recursionprocess
            elseif (is_array($value)) {
                $dbValue = $dbConfig[$key] ?? [];
                $result[$key] = is_array($dbValue)
                    ? $this->mergeConfigPreservingApiKeys($dbValue, $value)
                    : $value;
            }
            // other情况directlyusefront端value
            else {
                $result[$key] = $value;
            }
        }

        // front端not传field, thendatabasemiddlefieldfordefaultvalue ''
        foreach ($dbConfig as $key => $value) {
            if (! array_key_exists($key, $result)) {
                $result[$key] = '';
            }
        }

        return $result;
    }
}
