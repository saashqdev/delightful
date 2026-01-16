<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\Share\Factory;

use Delightful\BeDelightful\Application\Share\Adapter\FileShareableResource;
use Delightful\BeDelightful\Application\Share\Adapter\ProjectShareableResource;
use Delightful\BeDelightful\Application\Share\Adapter\TopicShareableResource;
use Delightful\BeDelightful\Application\Share\Factory\Facade\ResourceFactoryInterface;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use RuntimeException;
/** * SimpleResourceFactoryAccording toResourceTypeReturn corresponding ImplementationClassInstance. */

class ShareableResourceFactory 
{
 /** * According toResourceTypeCreatecorresponding ResourceFactoryInstance. * * @param ResourceType $resourceType ResourceType * @return ResourceFactoryInterface ResourceFactoryInterfaceImplementation * @throws RuntimeException WhenResourceTypedoes not support Throw */ 
    public function create(ResourceType $resourceType): ResourceFactoryInterface 
{
 // According toResourceTypeReturn corresponding Implementation $implementation = match ($resourceType) 
{
 ResourceType::Topic => TopicShareableResource::class, ResourceType::Project => ProjectShareableResource::class, ResourceType::File => FileShareableResource::class, // CanAddMoreResourceTypeMap // ResourceType::Document => DocumentShareableResource::class, // ResourceType::Knowledge => KnowledgeShareableResource::class, default => null 
}
; if ($implementation === null) 
{
 throw new RuntimeException( sprintf('does not support ResourceType: %s', $resourceType->name) ); 
}
 // try Throughincluding erGetInstance return Di($implementation); 
}
 
}
 
