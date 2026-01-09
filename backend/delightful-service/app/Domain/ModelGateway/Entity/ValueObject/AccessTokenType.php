<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ModelGateway\Entity\ValueObject;

/**
 * 访问令牌type: user、organization、应用.
 * 应用/user是跨organization的.
 */
enum AccessTokenType: string
{
    /**
     * 个人版.
     */
    case User = 'user';

    /**
     * 企业版. 其实现在还没有.
     */
    case Organization = 'organization';

    /**
     * 应用版.
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
