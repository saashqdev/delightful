<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\Share\Factory;

use Delightful\BeDelightful\Application\Share\Adapter\FileShareableResource;
use Delightful\BeDelightful\Application\Share\Adapter\ProjectShareableResource;
use Delightful\BeDelightful\Application\Share\Adapter\TopicShareableResource;
use Delightful\BeDelightful\Application\Share\Factory\Facade\ResourceFactoryInterface;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use RuntimeException;

/**
 * Simple resource factory that returns corresponding implementation class instances based on resource type.
 */
class ShareableResourceFactory
{
    /**
     * Create corresponding resource factory instance based on resource type.
     *
     * @param ResourceType $resourceType Resource type
     * @return ResourceFactoryInterface Resource factory interface implementation
     * @throws RuntimeException Throws when resource type is not supported
     */
    public function create(ResourceType $resourceType): ResourceFactoryInterface
    {
        // Return corresponding implementation based on resource type
        $implementation = match ($resourceType) {
            ResourceType::Topic => TopicShareableResource::class,
            ResourceType::Project => ProjectShareableResource::class,
            ResourceType::File => FileShareableResource::class,
            // Can add more resource type mappings
            // ResourceType::Document => DocumentShareableResource::class,
            // ResourceType::Knowledge => KnowledgeShareableResource::class,
            default => null
        };

        if ($implementation === null) {
            throw new RuntimeException(
                sprintf('Unsupported resource type: %s', $resourceType->name)
            );
        }

        // Try to get instance through container
        return Di($implementation);
    }
}
