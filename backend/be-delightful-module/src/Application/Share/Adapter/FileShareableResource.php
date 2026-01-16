<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\Share\Adapter;

use Delightful\BeDelightful\Application\Share\DTO\ShareableResourceDTO;
use Delightful\BeDelightful\Application\Share\Factory\Facade\ResourceFactoryInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
/** * File shareable resource adapter. */

class FileShareableResource implements ResourceFactoryInterface 
{
 
    protected LoggerInterface $logger; 
    public function __construct( LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(self::class); 
}
 /** * Get file content for sharing. */ 
    public function getResourceContent(string $resourceId, string $userId, string $organizationCode, int $page, int $pageSize): array 
{
 return []; 
}
 /** * Get file name. */ 
    public function getResourceName(string $resourceId): string 
{
 return ''; 
}
 /** * check if file is shareable. */ 
    public function isResourceShareable(string $resourceId, string $organizationCode): bool 
{
 return false; 
}
 /** * check if user has permission to share the file. */ 
    public function hasSharepermission (string $resourceId, string $userId, string $organizationCode): bool 
{
 return false; 
}
 /** * Extend file share list with additional info. */ 
    public function getResourceExtendlist (array $list): array 
{
 return []; 
}
 /** * Create resource DTO. */ 
    public function createResource(string $resourceId, string $userId, string $organizationCode): ShareableResourceDTO 
{
 return new ShareableResourceDTO(); 
}
 
}
 
