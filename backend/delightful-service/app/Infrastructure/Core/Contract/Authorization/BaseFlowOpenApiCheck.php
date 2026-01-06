<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Authorization;

use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Service\DelightfulFlowApiKeyDomainService;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Flow\DTO\DelightfulFlowApiChatDTO;

class BaseFlowOpenApiCheck implements FlowOpenApiCheckInterface
{
    public function handle(DelightfulFlowApiChatDTO $magicFlowApiChatDTO): DelightfulUserAuthorization
    {
        $authOptions = $this->getAuthOptions($magicFlowApiChatDTO);
        return match ($authOptions['type']) {
            'api-key' => $this->apiKey($magicFlowApiChatDTO, $authOptions['authorization']),
            default => ExceptionBuilder::throw(FlowErrorCode::AccessDenied, 'error authorization type'),
        };
    }

    /**
     * @return array{type: string, authorization: string}
     */
    protected function getAuthOptions(DelightfulFlowApiChatDTO $magicFlowApiChatDTO): array
    {
        $data = [
            'type' => '',
            'authorization' => '',
        ];
        if (! empty($magicFlowApiChatDTO->getApiKey())) {
            $data['type'] = 'api-key';
            $data['authorization'] = $magicFlowApiChatDTO->getApiKey();
            return $data;
        }
        $authorization = $magicFlowApiChatDTO->getAuthorization();
        if (str_starts_with($magicFlowApiChatDTO->getAuthorization(), 'Bearer ')) {
            $authorization = substr(trim($magicFlowApiChatDTO->getAuthorization()), 7);
        }
        // 还是 api-key
        if (str_starts_with($authorization, 'api-sk-')) {
            $data['type'] = 'api-key';
            $data['authorization'] = $authorization;
            return $data;
        }
        ExceptionBuilder::throw(FlowErrorCode::AccessDenied, 'error authorization');
    }

    protected function apiKey(DelightfulFlowApiChatDTO $magicFlowApiChatDTO, string $authorization): DelightfulUserAuthorization
    {
        $apiKey = di(DelightfulFlowApiKeyDomainService::class)->getBySecretKey(FlowDataIsolation::create()->disabled(), $authorization);
        $magicUserAuthorization = new DelightfulUserAuthorization();
        $magicUserAuthorization
            ->setId($apiKey->getCreator())
            ->setOrganizationCode($apiKey->getOrganizationCode())
            ->setUserType(UserType::Human)
            ->setDelightfulEnvId(0);
        if (empty($magicFlowApiChatDTO->getConversationId())) {
            $magicFlowApiChatDTO->setConversationId($apiKey->getConversationId());
        }
        $magicFlowApiChatDTO->setFlowCode($apiKey->getFlowCode());
        $user = di(DelightfulUserDomainService::class)->getByUserId($apiKey->getCreator());
        $magicFlowApiChatDTO->addShareOptions('user', $user);
        $magicFlowApiChatDTO->addShareOptions('source_id', 'sk_flow');
        return $magicUserAuthorization;
    }
}
