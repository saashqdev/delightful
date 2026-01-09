<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Authentication\DTO;

use App\Infrastructure\Core\AbstractDTO;

/**
 * loginrequestDTO.
 */
class CheckLoginRequest extends AbstractDTO
{
    /**
     * mailbox.
     */
    protected string $email = '';

    /**
     * password
     */
    protected string $password;

    /**
     * organizationencoding,not传defaultfornull.
     */
    protected string $organizationCode = '';

    /**
     * countrycode
     */
    protected string $stateCode = '+86';

    /**
     * handmachinenumber.
     */
    protected string $phone = '';

    /**
     * redirecttoURL.
     */
    protected string $redirect = '';

    /**
     * logintype.
     */
    protected string $type = 'email_password';

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }

    public function getStateCode(): string
    {
        return $this->stateCode;
    }

    public function setStateCode(string $stateCode): void
    {
        $this->stateCode = $stateCode;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getRedirect(): string
    {
        return $this->redirect;
    }

    public function setRedirect(string $redirect): void
    {
        $this->redirect = $redirect;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
