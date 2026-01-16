<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Share\Service;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
use Delightful\BeDelightful\Domain\Share\Repository\Facade\ResourceShareRepositoryInterface;
use Delightful\BeDelightful\ErrorCode\ShareErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\PasswordCrypt;
use Delightful\BeDelightful\Infrastructure\Utils\ShareCodeGenerator;
use Exception;
/** * ResourceShareService. */

class ResourceShareDomainService 
{
 
    public function __construct( 
    protected ResourceShareRepositoryInterface $shareRepository ) 
{
 
}
 
    public function saveShareByEntity(ResourceShareEntity $shareEntity): ResourceShareEntity 
{
 try 
{
 return $this->shareRepository->save($shareEntity); 
}
 catch (Exception $e) 
{
 // NewThrowException ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED, 'share.cancel_failed: ' . $shareEntity->getId()); 
}
 
}
 /** * cancel Sharedelete . * * @param int $shareId ShareID * @param string $userId user ID * @param string $organizationCode OrganizationCode * @return bool whether cancel Success * @throws Exception Ifcancel ShareFailed */ 
    public function cancelShare(int $shareId, string $userId, string $organizationCode): bool 
{
 // 1. Get share entity $shareEntity = $this->shareRepository->getShareById($shareId); // 2. Validate Sharewhether Exist if (! $shareEntity) 
{
 ExceptionBuilder::throw(ShareErrorCode::NOT_FOUND, 'share.not_found', [$shareId]); 
}
 // 3. Validate whether Havepermission cancel Shareonly Sharecreator or AdminCancancel  if ($shareEntity->getCreatedUid() !== $userId) 
{
 // CanAddExtrapermission check check user whether yes Admin ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.no_permission_to_cancel', [$shareId]); 
}
 // 4. Validate Organizationwhether Match if ($shareEntity->getOrganizationCode() !== $organizationCode) 
{
 ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.organization_mismatch', [$shareId]); 
}
 // 5. Set Deletion timeUpdateinfo $shareEntity->setdelete dAt(date('Y-m-d H:i:s')); $shareEntity->setUpdatedAt(date('Y-m-d H:i:s')); $shareEntity->setUpdatedUid($userId); // 6. Save try 
{
 $this->shareRepository->save($shareEntity); return true; 
}
 catch (Exception $e) 
{
 // NewThrowException ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED, 'share.cancel_failed', [$shareId]); 
}
 
}
 /** * GetShareDetails. * * @param string $resourceId ResourceID * @return null|ResourceShareEntity Share */ 
    public function getShareByResourceId(string $resourceId): ?ResourceShareEntity 
{
 return $this->shareRepository->getShareByResourceId($resourceId); 
}
 
    public function getShareByCode(string $code): ?ResourceShareEntity 
{
 return $this->shareRepository->getShareByCode($code); 
}
 /** * GetValidShare * ValidShareyes delete and not expired Share. * * @param string $shareId ShareID * @return null|ResourceShareEntity Share */ 
    public function getValidShareById(string $shareId): ?ResourceShareEntity 
{
 $share = $this->shareRepository->getShareById((int) $shareId); if (! $share || ! $share->isValid()) 
{
 return null; 
}
 return $share; 
}
 /** * ThroughShareGetValidShare. * * @param string $shareCode Share * @return null|ResourceShareEntity Share */ 
    public function getValidShareByCode(string $shareCode): ?ResourceShareEntity 
{
 $share = $this->shareRepository->getShareByCode($shareCode); if (! $share || ! $share->isValid()) 
{
 return null; 
}
 return $share; 
}
 /** * IncreaseShareView. * * @param string $shareId ShareID * @return bool whether Success */ 
    public function incrementViewCount(string $shareId): bool 
{
 $share = $this->shareRepository->getShareById((int) $shareId); if (! $share) 
{
 return false; 
}
 $share->incrementViewCount(); $this->shareRepository->save($share); return true; 
}
 
    public function getSharelist (int $page, int $pageSize, array $conditions = [], string $select = '*'): array 
{
 // need Return Fieldlist $allowedFields = [ 'id', 'resource_id', 'resource_name', 'resource_type', 'created_at', 'created_uid', 'share_type', ]; $result = $this->shareRepository->paginate($conditions, $page, $pageSize); // FilterField $filteredlist = []; foreach ($result['list'] as $item) 
{
 $filteredItem = []; // as Array $itemArray = $item instanceof ResourceShareEntity ? $item->toArray() : (array) $item; // AllowField foreach ($allowedFields as $field) 
{
 if (isset($itemArray[$field])) 
{
 $filteredItem[$field] = $itemArray[$field]; 
}
 
}
 $filteredlist [] = $filteredItem; 
}
 return ['total' => $result['total'], 'list' => $filteredlist ]; 
}
 /** * SaveShareCreateor Update. * * @param string $resourceId ResourceID * @param int $resourceType ResourceType * @param string $userId user ID * @param string $organizationCode OrganizationCode * @param array $attributes ExtraProperty * @param null|string $password PasswordOptional * @param null|int $expireDays Expiration timeOptional * @return ResourceShareEntity SaveShare * @throws Exception IfFailed */ 
    public function saveShare( string $resourceId, int $resourceType, string $userId, string $organizationCode, array $attributes = [], ?string $password = null, ?int $expireDays = null ): ResourceShareEntity 
{
 // 1. Findwhether Already existsShare $shareEntity = $this->findExistingShare($resourceId, $resourceType, ''); // 2. If does not exist, create new share entity if (! $shareEntity) 
{
 // Generate Share - Usingshare_codeUsing resource_id Share $shareCode = $attributes['share_code'] ?? $resourceId; // Buildbasic ShareData $shareData = [ 'resource_id' => $resourceId, 'resource_type' => $resourceType, 'resource_name' => $attributes['resource_name'], 'share_code' => $shareCode, 'share_type' => $attributes['share_type'] ?? 0, 'created_uid' => $userId, 'organization_code' => $organizationCode, ]; // CreateNew $shareEntity = new ResourceShareEntity($shareData); // Set Creation time $shareEntity->setCreatedAt(date('Y-m-d H:i:s')); 
}
 // 3. UpdatePropertyyes Newyes Already exists // UpdateShareTypeIf if (isset($attributes['share_type'])) 
{
 $shareEntity->setShareType($attributes['share_type']); 
}
 // UpdateExtraPropertyIf if (isset($attributes['extra'])) 
{
 $shareEntity->setExtra($attributes['extra']); 
}
 // Set PasswordIf if (! empty($password)) 
{
 // UsingEncryptSubstitute $shareEntity->setPassword(PasswordCrypt::encrypt($password)); 
}
 else 
{
 $shareEntity->setPassword(''); 
}
 $shareEntity->setIsPasswordEnabled((bool) $shareEntity->getPassword()); // Set Expiration timeIf if ($expireDays > 0) 
{
 // EnsureExpiration timeyes StringFormat $expireAt = date('Y-m-d H:i:s', strtotime( +
{
$expireDays
}
 days )); $shareEntity->setExpireAt($expireAt); 
}
 // Set Updateinfo $shareEntity->setUpdatedAt(date('Y-m-d H:i:s')); $shareEntity->setUpdatedUid($userId); $shareEntity->setdelete dAt(null); // 4. Save try 
{
 return $this->shareRepository->save($shareEntity); 
}
 catch (Exception $e) 
{
 // NewThrowException ExceptionBuilder::throw( ShareErrorCode::OPERATION_FAILED, 'share.save_failed', [$shareEntity->getId() ?: '(new)'] ); 
}
 
}
 /** * Generate Share. * * @return string Generate Share12Character */ 
    public function generateShareCode(): string 
{
 return (new ShareCodeGenerator()) ->setCodeLength(12) // Set as 12 ->generate(); 
}
 /** * According toIDNewGenerate Share. * * @param int $shareId ShareID * @throws Exception IfFailed */ 
    public function regenerateShareCodeById(int $shareId): ResourceShareEntity 
{
 // 1. Get share entity $shareEntity = $this->shareRepository->getShareById($shareId); if (! $shareEntity) 
{
 ExceptionBuilder::throw(ShareErrorCode::NOT_FOUND); 
}
 // 3. NewGenerate Share $newShareCode = $this->generateShareCode(); $shareEntity->setShareCode($newShareCode); $shareEntity->setUpdatedAt(date('Y-m-d H:i:s')); // 4. SaveUpdate try 
{
 $this->shareRepository->save($shareEntity); return $shareEntity; 
}
 catch (Exception $e) 
{
 ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED); 
}
 
}
 /** * ModifyPassword. * * @param int $shareId ShareID * @throws Exception IfFailed */ 
    public function changePasswordById(int $shareId, string $password): ResourceShareEntity 
{
 // 1. Get share entity $shareEntity = $this->shareRepository->getShareById($shareId); if (! $shareEntity) 
{
 ExceptionBuilder::throw(ShareErrorCode::NOT_FOUND); 
}
 // 3. Set Password if (! empty($password)) 
{
 // UsingEncryptSubstitute $shareEntity->setPassword(PasswordCrypt::encrypt($password)); 
}
 else 
{
 $shareEntity->setPassword(''); 
}
 $shareEntity->setIsPasswordEnabled((bool) $shareEntity->getPassword()); $shareEntity->setUpdatedAt(date('Y-m-d H:i:s')); // 4. SaveUpdate try 
{
 $this->shareRepository->save($shareEntity); return $shareEntity; 
}
 catch (Exception $e) 
{
 ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED); 
}
 
}
 /** * GetDecryptSharePassword * * @param ResourceShareEntity $shareEntity Share * @return string DecryptPassword */ 
    public function getDecryptedPassword(ResourceShareEntity $shareEntity): string 
{
 $encryptedPassword = $shareEntity->getPassword(); if (empty($encryptedPassword)) 
{
 return ''; 
}
 return PasswordCrypt::decrypt($encryptedPassword); 
}
 /** * Validate SharePasswordwhether Correct. * * @param ResourceShareEntity $shareEntity Share * @param string $password Validate Password * @return bool Passwordwhether Correct */ 
    public function verifyPassword(ResourceShareEntity $shareEntity, string $password): bool 
{
 if (empty($shareEntity->getPassword())) 
{
 return true; // PasswordSharedirectly Return Validate Through 
}
 $decryptedPassword = $this->getDecryptedPassword($shareEntity); return $decryptedPassword === $password; 
}
 /** * ShareStatusEnabled/Disabled. * * @param int $shareId ShareID * @param bool $enabled whether Enabled * @param string $userId user ID * @return ResourceShareEntity UpdateShare * @throws Exception IfFailed */ 
    public function toggleShareStatus(int $shareId, bool $enabled, string $userId): ResourceShareEntity 
{
 // 1. Get share entity $shareEntity = $this->shareRepository->getShareById($shareId); if (! $shareEntity) 
{
 ExceptionBuilder::throw(ShareErrorCode::NOT_FOUND, 'share.not_found', [$shareId]); 
}
 // 2. permission check (only creator can operate) if ($shareEntity->getCreatedUid() !== $userId) 
{
 ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.no_permission', [$shareId]); 
}
 // 3. UpdateEnabledStatus $shareEntity->setIsEnabled($enabled); $shareEntity->setUpdatedAt(date('Y-m-d H:i:s')); $shareEntity->setUpdatedUid($userId); // 4. SaveReturn try 
{
 return $this->shareRepository->save($shareEntity); 
}
 catch (Exception $e) 
{
 ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED, 'share.toggle_status_failed', [$shareId]); 
}
 
}
 /** * Getspecified ResourceShare. * * @param string $resourceId ResourceID * @param int $resourceType ResourceType * @return null|ResourceShareEntity Share */ 
    public function getShareByResource(string $resourceId, int $resourceType): ?ResourceShareEntity 
{
 return $this->shareRepository->getShareByResource('', $resourceId, $resourceType, false); 
}
 /** * delete specified ResourceShare. * * @param string $resourceId ResourceID * @param int $resourceType ResourceType * @param string $userId user IDOptionalfor permission check  * @param bool $forcedelete whether Forcedelete delete Defaultfalseas delete * @return bool delete whether Success */ 
    public function deleteShareByResource(string $resourceId, int $resourceType, string $userId = '', bool $forcedelete = false): bool 
{
 $shareEntity = $this->shareRepository->getShareByResource($userId, $resourceId, $resourceType); if (! $shareEntity) 
{
 return true; // Ifdoes not existas delete Success 
}
 return $this->shareRepository->delete($shareEntity->getId(), $forcedelete ); 
}
 /** * delete specified ShareShare. * * @param string $shareCode Share * @return bool delete whether Success */ 
    public function deleteShareByCode(string $shareCode): bool 
{
 $shareEntity = $this->shareRepository->getShareByCode($shareCode); if (! $shareEntity) 
{
 return true; // Ifdoes not existas delete Success 
}
 return $this->shareRepository->delete($shareEntity->getId()); 
}
 /** * Batchdelete specified ResourceTypeShare. * * @param string $resourceId ResourceID * @param int $resourceType ResourceType * @return bool delete whether Success */ 
    public function deleteAllSharesByResource(string $resourceId, int $resourceType): bool 
{
 try 
{
 // CanExtensionas Batchdelete Singledelete $shareEntity = $this->shareRepository->getShareByResource('', $resourceId, $resourceType); if (! $shareEntity) 
{
 return true; 
}
 return $this->shareRepository->delete($shareEntity->getId()); 
}
 catch (Exception $e) 
{
 ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED, 'share.delete_failed: ' . $resourceId); 
}
 
}
 /** * FindAlready existsShare. * * @param string $resourceId ResourceID * @param int $resourceType ResourceType * @param string $userId user ID * @return null|ResourceShareEntity IfExistReturn ShareOtherwiseReturn null */ 
    protected function findExistingShare(string $resourceId, int $resourceType, string $userId = ''): ?ResourceShareEntity 
{
 return $this->shareRepository->getShareByResource($userId, $resourceId, $resourceType); 
}
 
}
 
