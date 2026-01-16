<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\Share\Service;

use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Application\Share\Factory\ShareableResourceFactory;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Constant\ShareAccessType;
use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\ErrorCode\ShareErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\AccessTokenUtil;
use Delightful\BeDelightful\Infrastructure\Utils\PasswordCrypt;
use Delightful\BeDelightful\Interfaces\Share\Assembler\ShareAssembler;
use Delightful\BeDelightful\Interfaces\Share\DTO\Request\CreateShareRequestDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Request\GetShareDetailDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Request\Resourcelist RequestDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Response\ShareItemDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Response\ShareItemWithPasswordDTO;
use Exception;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;
/** * ResourceShareApplyService. */

class ResourceShareAppService extends AbstractShareAppService 
{
 
    protected LoggerInterface $logger; 
    public function __construct( 
    private ShareableResourceFactory $resourceFactory, 
    private ResourceShareDomainService $shareDomainService, 
    private ShareAssembler $shareAssembler, 
    public readonly LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(get_class($this)); 
}
 /** * CreateShare. * * @param Magicuser Authorization $userAuthorization current user * @param CreateShareRequestDTO $dto CreateShareRequestDTO * @return ShareItemDTO ShareItemDTO * @throws Exception CreateShareException */ 
    public function createShare(Magicuser Authorization $userAuthorization, CreateShareRequestDTO $dto): ShareItemDTO 
{
 $resourceId = $dto->resourceId; $userId = $userAuthorization->getId(); $organizationCode = $userAuthorization->getOrganizationCode(); // Validate ResourceType $resourceType = ResourceType::from($dto->resourceType); // 1. GetPairTypeResourceFactory try 
{
 $factory = $this->resourceFactory->create($resourceType); 
}
 catch (RuntimeException $e) 
{
 // Using ExceptionBuilder Throwdoes not support ResourceTypeException ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported', [$resourceType->name]); 
}
 // 2. Validate Resourcewhether Existand Share if (! $factory->isResourceShareable($resourceId, $organizationCode)) 
{
 ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.resource_not_found_or_not_shareable', [$resourceId]); 
}
 // 3. Validate Resourceowner permission if (! $factory->hasSharepermission ($resourceId, $userId, $organizationCode)) 
{
 ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.no_permission_to_share', [$resourceId]); 
}
 // 4. SaveShareCreateor Update- UsingService $attributes = [ 'resource_name' => $factory->getResourceName($resourceId), 'share_type' => $dto->shareType ?? ShareAccessType::Internet->value, ]; try 
{
 $savedEntity = $this->shareDomainService->saveShare( $resourceId, $resourceType->value, $userId, $organizationCode, $attributes, $dto->password, $dto->expireDays ); 
}
 catch (Exception $e) 
{
 $this->logger->error('Savetopic ShareFailedResult: ' . $e->getMessage()); ExceptionBuilder::throw(ShareErrorCode::CREATE_RESOURCES_ERROR, 'share.create_resources_error', [$resourceId]); 
}
 // 5. BuildResponse return $this->shareAssembler->toDto($savedEntity); 
}
 /** * cancel Share. * * @param Magicuser Authorization $userAuthorization current user * @param int $shareId ShareID * @return bool whether Success * @throws Exception cancel Shareoccurred Exception */ 
    public function cancelShare(Magicuser Authorization $userAuthorization, int $shareId): bool 
{
 $userId = $userAuthorization->getId(); $organizationCode = $userAuthorization->getOrganizationCode(); // call Servicecancel ShareMethod return $this->shareDomainService->cancelShare($shareId, $userId, $organizationCode); 
}
 /** * @throws Exception */ 
    public function cancelShareByResourceId(Magicuser Authorization $userAuthorization, string $resourceId): bool 
{
 $userId = $userAuthorization->getId(); $organizationCode = $userAuthorization->getOrganizationCode(); $shareEntity = $this->shareDomainService->getShareByResourceId($resourceId); if (is_null($shareEntity)) 
{
 return false; 
}
 // Validate ResourceType $resourceType = ResourceType::from($shareEntity->getResourceType()); // 1. GetPairTypeResourceFactory try 
{
 $factory = $this->resourceFactory->create($resourceType); 
}
 catch (RuntimeException $e) 
{
 // Using ExceptionBuilder Throwdoes not support ResourceTypeException ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported', [$resourceType->name]); 
}
 // 2. Validate Resourceowner permission if (! $factory->hasSharepermission ($resourceId, $userId, $organizationCode)) 
{
 ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.no_permission_to_share', [$resourceId]); 
}
 $shareEntity->setdelete dAt(date('Y-m-d H:i:s')); $shareEntity->setUpdatedAt(date('Y-m-d H:i:s')); $shareEntity->setUpdatedUid($userId); // call Servicecancel ShareMethod $this->shareDomainService->saveShareByEntity($shareEntity); return true; 
}
 
    public function checkShare(?Magicuser Authorization $userAuthorization, string $shareCode): array 
{
 $shareEntity = $this->shareDomainService->getShareByCode($shareCode); if (empty($shareEntity)) 
{
 ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.not_found', [$shareCode]); 
}
 return [ 'has_password' => ! empty($shareEntity->getPassword()), 'user_id' => ! is_null($userAuthorization) ? $userAuthorization->getId() : '', ]; 
}
 
    public function getShareDetail(?Magicuser Authorization $userAuthorization, string $shareCode, GetShareDetailDTO $detailDTO): array 
{
 // GetDetailsContent $shareEntity = $this->shareDomainService->getShareByCode($shareCode); if (empty($shareEntity)) 
{
 ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.not_found', [$shareCode]); 
}
 // permission only Havepermission if ($shareEntity->getShareType() == ShareAccessType::SelfOnly->value && ($userAuthorization === null || $shareEntity->getCreatedUid() != $userAuthorization->getId())) 
{
 ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.permission_denied', [$shareCode]); 
}
 // DeterminePasswordwhether Correct if (! empty($shareEntity->getPassword()) && ($detailDTO->getPassword() != PasswordCrypt::decrypt($shareEntity->getPassword()))) 
{
 ExceptionBuilder::throw(ShareErrorCode::PASSWORD_ERROR, 'share.password_error', [$shareCode]); 
}
 // call FactoryClassGet ContentData try 
{
 $resourceType = ResourceType::tryFrom($shareEntity->getResourceType()); $factory = $this->resourceFactory->create($resourceType); 
}
 catch (RuntimeException $e) 
{
 // Using ExceptionBuilder Throwdoes not support ResourceTypeException ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported', [$resourceType->name]); 
}
 // Return Data return [ 'resource_type' => $resourceType, 'resource_name' => $shareEntity->getResourceName(), 'temporary_token' => AccessTokenUtil::generate((string) $shareEntity->getId(), $shareEntity->getOrganizationCode()), 'data' => $factory->getResourceContent( $shareEntity->getResourceId(), $shareEntity->getCreatedUid(), $shareEntity->getOrganizationCode(), $detailDTO->getPage(), $detailDTO->getPageSize() ), ]; 
}
 
    public function getSharelist (Magicuser Authorization $userAuthorization, Resourcelist RequestDTO $dto): array 
{
 $conditions = ['created_uid' => $userAuthorization->getId()]; if (! empty($dto->getKeyword())) 
{
 $conditions['keyword'] = $dto->getKeyword(); 
}
 $result = $this->shareDomainService->getSharelist ($dto->getPage(), $dto->getPageSize(), $conditions); if (empty($result)) 
{
 return [ 'total' => 0, 'list' => [], ]; 
}
 // Extensioninfo try 
{
 $resourceType = ResourceType::from($dto->getResourceType()); $factory = $this->resourceFactory->create($resourceType); 
}
 catch (RuntimeException $e) 
{
 // Using ExceptionBuilder Throwdoes not support ResourceTypeException ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported', [$resourceType->name]); 
}
 $result['list'] = $factory->getResourceExtendlist ($result['list']); return $result; 
}
 /** * ThroughSharecodeGetShareinfo Password. * * @param null|Magicuser Authorization $userAuthorization current user Canas null * @param string $shareCode Sharecode * @return ShareItemDTO ShareItemDTO * @throws Exception GetShareinfo Exception */ 
    public function getShareByCode(?Magicuser Authorization $userAuthorization, string $shareCode): ShareItemDTO 
{
 // Getand validate $shareEntity = $this->getAndValidateShareEntity($userAuthorization, $shareCode); // UsingCreateBaseDTOPassword return $this->shareAssembler->toDto($shareEntity); 
}
 /** * ThroughSharecodeGetShareinfo Password. * AttentionMethodonly AtUsingprocess Return Passwordinfo . * * @param null|Magicuser Authorization $userAuthorization current user Canas null * @param string $shareCode Sharecode * @return ShareItemWithPasswordDTO including PasswordShareItemDTO * @throws Exception GetShareinfo Exception */ 
    public function getShareWithPasswordByCode(?Magicuser Authorization $userAuthorization, string $shareCode): ShareItemWithPasswordDTO 
{
 // Getand validate try 
{
 $shareEntity = $this->getAndValidateShareEntity($userAuthorization, $shareCode); // if ($shareEntity->getCreatedUid() !== $userAuthorization->getId()) 
{
 // ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.permission_denied', [$shareCode]); // 
}
 // UsingCreateincluding PasswordDTO return $this->shareAssembler->toDtoWithPassword($shareEntity); 
}
 catch (BusinessException $e) 
{
 return new ShareItemWithPasswordDTO(); 
}
 
}
 /** * According toShare codeGet share entity. * * @param string $shareCode Share code * @return null|ResourceShareEntity ShareIfdoes not existReturn null */ 
    public function getShare(string $shareCode): ?ResourceShareEntity 
{
 return $this->shareDomainService->getShareByCode($shareCode); 
}
 /** * Getand validate Share. * * @param null|Magicuser Authorization $userAuthorization current user Canas null * @param string $shareCode Sharecode * @return ResourceShareEntity Validate ThroughShare * @throws Exception IfValidate failed */ 
    private function getAndValidateShareEntity(?Magicuser Authorization $userAuthorization, string $shareCode): ResourceShareEntity 
{
 // ThroughServiceGet share entity $shareEntity = $this->shareDomainService->getShareByCode($shareCode); // Validate Sharewhether Exist if (empty($shareEntity)) 
{
 ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.not_found', [$shareCode]); 
}
 // permission IfShareTypeyes only Visibleneed Validate user if ($shareEntity->getShareType() == ShareAccessType::SelfOnly->value && ($userAuthorization === null || $shareEntity->getCreatedUid() != $userAuthorization->getId())) 
{
 ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.permission_denied', [$shareCode]); 
}
 return $shareEntity; 
}
 
}
 
