<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\Share\Factory\Facade;

use Delightful\BeDelightful\Application\Share\DTO\ShareableResourceDTO;
use RuntimeException;
/** * ResourceFactoryInterface * for CreateResourceObjectFactoryInterface. */

interface ResourceFactoryInterface 
{
 /** * GetFactorySupportResourceTypeName. */ 
    public function getResourceName(string $resourceId): string; /** * Extensiontopic Sharelist Data. */ 
    public function getResourceExtendlist (array $list): array; /** * GetResourceContent. */ 
    public function getResourceContent(string $resourceId, string $userId, string $organizationCode, int $page, int $pageSize): array; /** * According toResourceIDCreateResourceObject * * @param string $resourceId ResourceID * @param string $userId user id * @param string $organizationCode OrganizationCode * @return ShareableResourceDTO ResourceObject * @throws RuntimeException WhenResourcedoes not existor cannot CreateResourceThrowException */ 
    public function createResource(string $resourceId, string $userId, string $organizationCode): ShareableResourceDTO; /** * check Resourcewhether Existand Can. * * @param string $resourceId ResourceID * @param string $organizationCode OrganizationCode * @return bool Resourcewhether Existand is shareable */ 
    public function isResourceShareable(string $resourceId, string $organizationCode): bool; /** * check user whether Havepermission Resource. * * @param string $resourceId ResourceID * @param string $userId user ID * @param string $organizationCode OrganizationCode * @return bool whether Havepermission */ 
    public function hasSharepermission (string $resourceId, string $userId, string $organizationCode): bool; 
}
 
