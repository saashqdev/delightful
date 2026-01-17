<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\Share\Factory\Facade;

use Delightful\BeDelightful\Application\Share\DTO\ShareableResourceDTO;
use RuntimeException;

/**
 * Resource factory interface
 * Factory interface for creating shareable resource objects.
 */
interface ResourceFactoryInterface
{
    /**
     * Get the business resource type name supported by the factory.
     */
    public function getResourceName(string $resourceId): string;

    /**
     * Extend the data of topic sharing list.
     */
    public function getResourceExtendList(array $list): array;

    /**
     * Get business resource content.
     */
    public function getResourceContent(string $resourceId, string $userId, string $organizationCode, int $page, int $pageSize): array;

    /**
     * Create a shareable resource object by resource ID
     *
     * @param string $resourceId Resource ID
     * @param string $userId User ID
     * @param string $organizationCode Organization code
     * @return ShareableResourceDTO Shareable resource object
     * @throws RuntimeException Throws exception when resource does not exist or cannot create shareable resource
     */
    public function createResource(string $resourceId, string $userId, string $organizationCode): ShareableResourceDTO;

    /**
     * Check if resource exists and can be shared.
     *
     * @param string $resourceId Resource ID
     * @param string $organizationCode Organization code
     * @return bool Whether resource exists and is shareable
     */
    public function isResourceShareable(string $resourceId, string $organizationCode): bool;

    /**
     * Check if user has permission to share the resource.
     *
     * @param string $resourceId Resource ID
     * @param string $userId User ID
     * @param string $organizationCode Organization code
     * @return bool Whether has share permission
     */
    public function hasSharePermission(string $resourceId, string $userId, string $organizationCode): bool;
}
