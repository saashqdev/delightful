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
     * userwhenfront的organizationencoding
     */
    protected string $organizationCode;

    /**
     * loginsuccessbackdownhair的token.
     */
    protected string $authorization;

    protected string $language = '';

    protected string $beDelightfulAgentUserId = '';

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
        return $this->beDelightfulAgentUserId;
    }
}
