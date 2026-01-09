<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ModelGateway\Entity\ValueObject;

/**
 * accesstokentype: user,organization,application.
 * application/useriscrossorganization.
 */
enum AccessTokenType: string
{
    /**
     * personversion.
     */
    case User = 'user';

    /**
     * enterpriseversion. itsimplementinalsonothave.
     */
    case Organization = 'organization';

    /**
     * applicationversion.
     */
    case Application = 'application';

    public function isUser(): bool
    {
        return $this === self::User;
    }

    public function isApplication(): bool
    {
        return $this === self::Application;
    }

    public function isOrganization(): bool
    {
        return $this === self::Organization;
    }
}
