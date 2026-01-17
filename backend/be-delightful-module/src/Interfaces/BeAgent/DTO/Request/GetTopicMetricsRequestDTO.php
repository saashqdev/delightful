<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use Hyperf\HttpServer\Contract\RequestInterface;

class GetTopicMetricsRequestDTO
{
    /**
     * @var string Organization code, optional
     */
    protected string $organizationCode = '';

    /**
     * Construct DTO from request.
     */
    public static function fromRequest(RequestInterface $request): self
    {
        $dto = new self();
        $dto->setOrganizationCode($request->input('organization_code', ''));
        return $dto;
    }

    /**
     * Get organization code
     */
    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    /**
     * Set organization code
     */
    public function setOrganizationCode(string $organizationCode): self
    {
        $this->organizationCode = $organizationCode;
        return $this;
    }
}
