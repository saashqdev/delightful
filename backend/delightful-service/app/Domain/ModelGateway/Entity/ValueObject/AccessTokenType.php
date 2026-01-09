<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ModelGateway\Entity\ValueObject;

/**
 * accesstokentype: user、organization、application.
 * application/user是跨organization的.
 */
enum AccessTokenType: string
{
    /**
     * person版.
     */
    case User = 'user';

    /**
     * 企业版. 其implementinalsonothave.
     */
    case Organization = 'organization';

    /**
     * application版.
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
