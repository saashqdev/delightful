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
 * AI能力applicationservice.
 */
class AiAbilityAppService extends AbstractKernelAppService
{
    public function __construct(
        private AiAbilityDomainService $aiAbilityDomainService,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * get所有AI能力list.
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
     * getAI能力detail.
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     * @param string $code 能力code
     */
    public function getDetail(DelightfulUserAuthorization $authorization, string $code): AiAbilityDetailDTO
    {
        $dataIsolation = $this->createProviderDataIsolation($authorization);

        // verifycode是否valid
        try {
            $codeEnum = AiAbilityCode::from($code);
        } catch (Throwable $e) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::AI_ABILITY_NOT_FOUND);
        }

        // get能力detail
        $entity = $this->aiAbilityDomainService->getByCode($dataIsolation, $codeEnum);

        $locale = $this->translator->getLocale();
        return AiAbilityAssembler::entityToDetailDTO($entity, $locale);
    }

    /**
     * updateAI能力.
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     * @param UpdateAiAbilityRequest $request updaterequest
     * @return bool 是否updatesuccess
     */
    public function update(DelightfulUserAuthorization $authorization, UpdateAiAbilityRequest $request): bool
    {
        $dataIsolation = $this->createProviderDataIsolation($authorization);

        // verifycode是否valid
        try {
            $code = AiAbilityCode::from($request->getCode());
        } catch (Throwable $e) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::AI_ABILITY_NOT_FOUND);
        }

        // buildupdatedata（support选择性update）
        $updateData = [];
        if ($request->hasStatus()) {
            $updateData['status'] = $request->getStatus();
        }
        if ($request->hasConfig()) {
            // getcurrentdatabase中的configuration
            $entity = $this->aiAbilityDomainService->getByCode($dataIsolation, $code);
            $dbConfig = $entity->getConfig();

            // 智能mergeconfiguration（保留被脱敏的api_key）
            $mergedConfig = $this->mergeConfigPreservingApiKeys($dbConfig, $request->getConfig());
            $updateData['config'] = $mergedConfig;
        }

        // 如果没有要update的data，直接returnsuccess
        if (empty($updateData)) {
            return true;
        }

        // pass DomainService update
        return $this->aiAbilityDomainService->updateByCode($dataIsolation, $code, $updateData);
    }

    /**
     * initializeAI能力data（从configurationfilesync到database）.
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     * @return int initialize的quantity
     */
    public function initializeAbilities(DelightfulUserAuthorization $authorization): int
    {
        $dataIsolation = $this->createProviderDataIsolation($authorization);

        return $this->aiAbilityDomainService->initializeAbilities($dataIsolation);
    }

    /**
     * 智能mergeconfiguration（保留被脱敏的api_keyoriginalvalue）.
     *
     * @param array $dbConfig databaseoriginalconfiguration
     * @param array $frontendConfig 前端传来的configuration（可能contain脱敏的api_key）
     * @return array merge后的configuration
     */
    private function mergeConfigPreservingApiKeys(array $dbConfig, array $frontendConfig): array
    {
        $result = [];

        // 遍历前端configuration的所有field
        foreach ($frontendConfig as $key => $value) {
            // 如果是 api_key field且contain脱敏mark ***
            if ($key === 'api_key' && is_string($value) && str_contains($value, '*')) {
                // usedatabase中的originalvalue
                $result[$key] = $dbConfig[$key] ?? $value;
            }
            // 如果是array，递归process
            elseif (is_array($value)) {
                $dbValue = $dbConfig[$key] ?? [];
                $result[$key] = is_array($dbValue)
                    ? $this->mergeConfigPreservingApiKeys($dbValue, $value)
                    : $value;
            }
            // 其他情况直接use前端的value
            else {
                $result[$key] = $value;
            }
        }

        // 前端未传的field, 则database中field为defaultvalue ''
        foreach ($dbConfig as $key => $value) {
            if (! array_key_exists($key, $result)) {
                $result[$key] = '';
            }
        }

        return $result;
    }
}
