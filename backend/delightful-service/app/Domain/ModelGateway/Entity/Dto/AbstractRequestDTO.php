<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ModelGateway\Entity\Dto;

use App\Domain\Chat\Entity\AbstractEntity;
use App\ErrorCode\DelightfulApiErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

abstract class AbstractRequestDTO extends AbstractEntity implements ProxyModelRequestInterface
{
    public const string METHOD_CHAT_COMPLETIONS = 'chat_completions';

    public const string METHOD_COMPLETIONS = 'completions';

    public const string METHOD_EMBEDDINGS = 'embeddings';

    /**
     * businessparameter，such asapplication版thenneed额outsideparameter.
     */
    protected array $businessParams = [];

    protected string $callMethod = self::METHOD_CHAT_COMPLETIONS;

    protected string $accessToken = '';

    protected string $model = '';

    protected array $ips = [];

    protected array $headerConfigs = [];

    protected bool $enableHighAvailability = true;

    public function getBusinessParam(string $key, bool $checkExists = false): mixed
    {
        $value = $this->businessParams[$key] ?? null;
        if ($checkExists && is_null($value)) {
            ExceptionBuilder::throw(DelightfulApiErrorCode::ValidateFailed, 'common.empty', ['label' => $key]);
        }
        return $value;
    }

    public function addBusinessParam(string $key, mixed $value): void
    {
        $this->businessParams[$key] = $value;
    }

    public function setBusinessParams(array $businessParams): void
    {
        $this->businessParams = $businessParams;
    }

    public function getBusinessParams(): array
    {
        return $this->businessParams;
    }

    public function getCallMethod(): string
    {
        return $this->callMethod;
    }

    public function setCallMethod(string $callMethod): void
    {
        $this->callMethod = $callMethod;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(int|string $model): void
    {
        $this->model = (string) $model;
    }

    public function getIps(): array
    {
        return $this->ips;
    }

    public function setIps(array $ips): void
    {
        $this->ips = $ips;
    }

    public function setHeaderConfigs(array $headerConfigs): void
    {
        $this->headerConfigs = $headerConfigs;
        $this->formatHeaderBusinessParams($headerConfigs);
    }

    public function getHeaderConfig(string $key, mixed $default = null): mixed
    {
        $key = strtolower($key);
        return $this->headerConfigs[$key] ?? $default;
    }

    public function getTopicId(): ?string
    {
        return $this->getHeaderConfig('delightful-topic-id') ?? '';
    }

    public function getTaskId(): ?string
    {
        return $this->getHeaderConfig('delightful-task-id');
    }

    public function isEnableHighAvailability(): bool
    {
        return $this->enableHighAvailability;
    }

    public function setEnableHighAvailability(bool $enableHighAvailability): void
    {
        $this->enableHighAvailability = $enableHighAvailability;
    }

    private function formatHeaderBusinessParams(array $headerConfigs): void
    {
        if (isset($headerConfigs['delightful-organization-id'])) {
            $this->businessParams['organization_id'] = $headerConfigs['delightful-organization-id'];
        }
        if (isset($headerConfigs['delightful-organization-code'])) {
            $this->businessParams['organization_id'] = $headerConfigs['delightful-organization-code'];
        }
        if (isset($headerConfigs['delightful-user-id'])) {
            $this->businessParams['user_id'] = $headerConfigs['delightful-user-id'];
        }
        if (isset($headerConfigs['business_id'])) {
            $this->businessParams['business_id'] = $headerConfigs['business_id'];
        }
        if (isset($headerConfigs['delightful-topic-id'])) {
            $this->businessParams['delightful_topic_id'] = $headerConfigs['delightful-topic-id'];
        }
        if (isset($headerConfigs['delightful-chat-topic-id'])) {
            $this->businessParams['delightful_chat_topic_id'] = $headerConfigs['delightful-chat-topic-id'];
        }
        if (isset($headerConfigs['delightful-task-id'])) {
            $this->businessParams['delightful_task_id'] = $headerConfigs['delightful-task-id'];
        }
        if (isset($headerConfigs['delightful-language'])) {
            $this->businessParams['language'] = $headerConfigs['delightful-language'];
        }
    }
}
