<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\DTO;

// delightful_api_premium_endpoint_responses
use App\Infrastructure\Core\AbstractDTO;

class EndpointResponseDTO extends AbstractDTO
{
    /**
     * primary keyID.
     */
    protected string $id;

    /**
     * requestID.
     */
    protected string $requestId;

    /**
     * 接入pointID.
     */
    protected string $endpointId;

    /**
     * requestparameterlength.
     */
    protected int $requestLength;

    /**
     * responseconsumetime,unit:毫second.
     */
    protected int $responseTime;

    /**
     * response http status码
     */
    protected int $httpStatusCode;

    /**
     * responsebusinessstatus码
     */
    protected int $businessStatusCode;

    /**
     * whetherrequestsuccess
     */
    protected int $isSuccess = 0;

    /**
     * exceptiontype.
     */
    protected ?string $exceptionType = null;

    /**
     * exceptioninformation.
     */
    protected ?string $exceptionMessage = null;

    /**
     * creation time.
     */
    protected string $createdAt;

    /**
     * update time.
     */
    protected string $updatedAt;

    /**
     * convenient debug o clocktrace哪within new it.
     * @param mixed $data
     */
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getId(): string
    {
        return $this->id ?? '';
    }

    public function setId(null|int|string $id): static
    {
        $this->id = (string) $id;
        return $this;
    }

    public function getRequestId(): string
    {
        return $this->requestId ?? '';
    }

    public function setRequestId(string $requestId): static
    {
        $this->requestId = $requestId;
        return $this;
    }

    public function getEndpointId(): string
    {
        return $this->endpointId ?? '';
    }

    public function setEndpointId(string $endpointId): static
    {
        $this->endpointId = $endpointId;
        return $this;
    }

    public function getRequestLength(): int
    {
        return $this->requestLength ?? 0;
    }

    public function setRequestLength(int $requestLength): static
    {
        $this->requestLength = $requestLength;
        return $this;
    }

    public function getResponseTime(): int
    {
        return $this->responseTime ?? 0;
    }

    public function setResponseTime(int $responseTime): static
    {
        $this->responseTime = $responseTime;
        return $this;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode ?? 0;
    }

    public function setHttpStatusCode(int $httpStatusCode): static
    {
        $this->httpStatusCode = $httpStatusCode;
        return $this;
    }

    public function getBusinessStatusCode(): int
    {
        return $this->businessStatusCode ?? 0;
    }

    public function setBusinessStatusCode(int $businessStatusCode): static
    {
        $this->businessStatusCode = $businessStatusCode;
        return $this;
    }

    public function isSuccess(): bool
    {
        return (bool) $this->isSuccess;
    }

    public function getIsSuccess(): int
    {
        return $this->isSuccess;
    }

    public function setIsSuccess(int $isSuccess): static
    {
        $this->isSuccess = $isSuccess;
        return $this;
    }

    public function getExceptionType(): ?string
    {
        return $this->exceptionType;
    }

    public function setExceptionType(?string $exceptionType): static
    {
        $this->exceptionType = $exceptionType;
        return $this;
    }

    public function getExceptionMessage(): ?string
    {
        return $this->exceptionMessage;
    }

    public function setExceptionMessage(?string $exceptionMessage): static
    {
        $this->exceptionMessage = $exceptionMessage;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt ?? '';
    }

    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt ?? '';
    }

    public function setUpdatedAt(string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
