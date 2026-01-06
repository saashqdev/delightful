<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Request\Common;

use App\Domain\Chat\Entity\AbstractEntity;

class DelightfulContext extends AbstractEntity
{
    /**
     * 用户当前的组织编码
     */
    protected string $organizationCode;

    /**
     * 登录成功后下发的token.
     */
    protected string $authorization;

    protected string $language = '';

    protected string $superDelightfulAgentUserId = '';

    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }

    public function getAuthorization(): string
    {
        return $this->authorization;
    }

    public function setAuthorization(string $authorization): void
    {
        $this->authorization = $authorization;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getBeDelightfulAgentUserId(): string
    {
        return $this->superDelightfulAgentUserId;
    }
}
