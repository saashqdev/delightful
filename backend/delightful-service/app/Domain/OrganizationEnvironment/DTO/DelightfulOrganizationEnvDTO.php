<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\DTO;

use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;

class DelightfulOrganizationEnvDTO extends DelightfulEnvironmentEntity
{
    protected string $orgEnvId;

    protected string $loginCode;

    protected string $delightfulOrganizationCode;

    protected string $originOrganizationCode;

    protected int $environmentId;

    protected ?DelightfulEnvironmentEntity $delightfulEnvironmentEntity = null;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function getDelightfulEnvironmentEntity(): ?DelightfulEnvironmentEntity
    {
        return $this->delightfulEnvironmentEntity;
    }

    public function setDelightfulEnvironmentEntity(?DelightfulEnvironmentEntity $delightfulEnvironmentEntity): void
    {
        $this->delightfulEnvironmentEntity = $delightfulEnvironmentEntity;
    }

    public function getOrgEnvId(): string
    {
        return $this->orgEnvId;
    }

    public function setOrgEnvId(string $orgEnvId): void
    {
        $this->orgEnvId = $orgEnvId;
    }

    public function getLoginCode(): string
    {
        return $this->loginCode;
    }

    public function setLoginCode(string $loginCode): void
    {
        $this->loginCode = $loginCode;
    }

    public function getDelightfulOrganizationCode(): string
    {
        return $this->delightfulOrganizationCode;
    }

    public function setDelightfulOrganizationCode(string $delightfulOrganizationCode): void
    {
        $this->delightfulOrganizationCode = $delightfulOrganizationCode;
    }

    public function getOriginOrganizationCode(): string
    {
        return $this->originOrganizationCode;
    }

    public function setOriginOrganizationCode(string $originOrganizationCode): void
    {
        $this->originOrganizationCode = $originOrganizationCode;
    }

    public function getEnvironmentId(): int
    {
        return $this->environmentId;
    }

    public function setEnvironmentId(int $environmentId): void
    {
        $this->environmentId = $environmentId;
    }
}
