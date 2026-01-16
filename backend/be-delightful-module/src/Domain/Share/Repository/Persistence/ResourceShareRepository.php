<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Share\Repository\Persistence;

use App\Infrastructure\Core\AbstractRepository;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
use Delightful\BeDelightful\Domain\Share\Repository\Facade\ResourceShareRepositoryInterface;
use Delightful\BeDelightful\Domain\Share\Repository\Model\ResourceShareModel;
use Exception;
use Hyperf\Codec\Json;
/** * ResourceShareImplementation. */

class ResourceShareRepository extends AbstractRepository implements ResourceShareRepositoryInterface 
{
 /** * Function. */ 
    public function __construct(
    protected ResourceShareModel $model) 
{
 
}
 /** * ThroughIDGetShare. * * @param int $shareId ShareID * @return null|ResourceShareEntity ResourceShare */ 
    public function getShareById(int $shareId): ?ResourceShareEntity 
{
 $model = $this->model->newquery ()->where('id', $shareId)->whereNull('deleted_at')->first(); return $model ? $this->modelToEntity($model) : null; 
}
 /** * ThroughShareGetShare. * * @param string $shareCode Share * @return null|ResourceShareEntity ResourceShare */ 
    public function getShareByCode(string $shareCode): ?ResourceShareEntity 
{
 $model = $this->model->newquery ()->where('share_code', $shareCode)->orderBy('id', 'desc')->first(); return $model ? $this->modelToEntity($model) : null; 
}
 
    public function getShareByResourceId(string $resourceId): ?ResourceShareEntity 
{
 $model = $this->model->newquery ()->where('resource_id', $resourceId)->first(); return $model ? $this->modelToEntity($model) : null; 
}
 /** * FindResourcecorresponding Share. * * @param string $resourceId ResourceID * @param ResourceType $resourceType ResourceType * @param string $userId user ID * @return null|ResourceShareEntity ResourceShare */ 
    public function findByResource(string $resourceId, ResourceType $resourceType, string $userId): ?ResourceShareEntity 
{
 $model = $this->model->newquery () ->where('resource_id', $resourceId) ->where('resource_type', $resourceType->value) ->where('creator', $userId) ->first(); return $model ? $this->modelToEntity($model) : null; 
}
 /** * CreateSharerecord . * * @param array $data ShareData * @return string ShareID */ 
    public function create(array $data): string 
{
 $model = new $this->model(); $model->fill($data); $model->save(); return (string) $model->id; 
}
 /** * SaveShare. * * @param ResourceShareEntity $shareEntity ResourceShare * @return ResourceShareEntity SaveResourceShare * @throws Exception */ 
    public function save(ResourceShareEntity $shareEntity): ResourceShareEntity 
{
 $data = $this->entityToArray($shareEntity); try 
{
 if ($shareEntity->getId() === 0) 
{
 // Createnew record $data['id'] = IdGenerator::getSnowId(); $model = $this->model::query()->create($data); $shareEntity->setId((int) $model->id); 
}
 else 
{
 // UpdateHaverecord $this->model->query()->withTrashed()->where('id', $shareEntity->getId())->update($data); 
}
 return $shareEntity; 
}
 catch (Exception $e) 
{
 throw new Exception('SaveShareFailed' . $e->getMessage()); 
}
 
}
 /** * delete Share. * * @param int $shareId ShareID * @param bool $forcedelete whether Forcedelete delete Defaultfalseas delete * @return bool whether Success */ 
    public function delete(int $shareId, bool $forcedelete = false): bool 
{
 if ($forcedelete ) 
{
 // delete directly FromDatabasedelete $model = $this->model->newquery ()->withTrashed()->find($shareId); if ($model) 
{
 return $model->forcedelete (); 
}
 
}
 else 
{
 // delete Using Softdelete s

trait $model = $this->model->newquery ()->find($shareId); if ($model) 
{
 return $model->delete(); 
}
 
}
 return false; 
}
 /** * IncreaseShareView. * * @param string $shareCode Share * @return bool whether Success */ 
    public function incrementViewCount(string $shareCode): bool 
{
 $result = $this->model->newquery () ->where('share_code', $shareCode) ->increment('view_count'); return $result > 0; 
}
 /** * check Sharewhether Already exists. * * @param string $shareCode Share * @return bool whether Already exists */ 
    public function isShareCodeExists(string $shareCode): bool 
{
 return $this->model->newquery ()->where('share_code', $shareCode)->exists(); 
}
 /** * GetSharerecord list . * * @param array $conditions Condition * @param int $page Page number * @param int $pageSize Per pageQuantity * @return array PagingData [total, list] */ 
    public function paginate(array $conditions, int $page = 1, int $pageSize = 20): array 
{
 $query = $this->model->newquery (); // Addquery Condition foreach ($conditions as $field => $value) 
{
 if ($field == 'keyword') 
{
 $query->where($field, 'LIKE', '%' . $value . '%'); 
}
 else 
{
 if ($value !== null) 
{
 $query->where($field, $value); 
}
 
}
 
}
 // Creation timeReverseSort $query->orderBy('id', 'desc'); $query->whereNull('deleted_at'); // Calculate Total $total = $query->count(); // GetPagingData $models = $query->offset(($page - 1) * $pageSize) ->limit($pageSize) ->get(); // ModelConvert to $items = []; foreach ($models as $model) 
{
 $items[] = $this->modelToEntity($model); 
}
 return [ 'total' => $total, 'list' => $items, ]; 
}
 
    public function getShareByResource(string $userId, string $resourceId, int $resourceType, bool $withTrashed = true): ?ResourceShareEntity 
{
 if ($withTrashed) 
{
 $query = ResourceShareModel::query()->withTrashed(); 
}
 else 
{
 $query = ResourceShareModel::query(); 
}
 if (! empty($userId)) 
{
 $query = $query->where('created_uid', $userId); 
}
 $model = $query->where('resource_id', $resourceId)->where('resource_type', $resourceType)->first(); if (! $model) 
{
 return null; 
}
 return $this->modelToEntity($model); 
}
 /** * POModelConvert to. * * @param ResourceShareModel $model Model * @return ResourceShareEntity */ 
    protected function modelToEntity(ResourceShareModel $model): ?ResourceShareEntity 
{
 $entity = new ResourceShareEntity(); $entity->setId((int) $model->id); $entity->setResourceId((string) $model->resource_id); $entity->setResourceType((int) $model->resource_type); $entity->setResourceName((string) $model->resource_name); $entity->setShareCode(htmlspecialchars($model->share_code, ENT_QUOTES, 'UTF-8')); $entity->setShareType((int) $model->share_type); $entity->setPassword($model->password); // Safeprocess - EnsureFormatCorrect if ($model->expire_at) 
{
 $entity->setExpireAt($model->expire_at); 
}
 $entity->setViewCount($model->view_count); $entity->setCreatedUid($model->created_uid); $entity->setOrganizationCode(htmlspecialchars($model->organization_code, ENT_QUOTES, 'UTF-8')); // process TargetIDIfin HaveField /* @phpstan-ignore-next-line */ if (property_exists($model, 'target_ids') && method_exists($entity, 'setTargetIds')) 
{
 $entity->setTargetIds($model->target_ids ?? '[]'); 
}
 $extra = $model->extra ?? []; $extra = is_array($extra) ? $extra : Json::decode($extra); $entity->setExtra($extra); // process whether EnabledFieldInviteLink $entity->setIsEnabled($model->is_enabled ?? true); // process PasswordProtectedwhether EnabledField $entity->setIsPasswordEnabled($model->is_password_enabled ?? false); if ($model->created_at) 
{
 $entity->setCreatedAt($model->created_at); 
}
 if ($model->updated_at) 
{
 $entity->setUpdatedAt($model->updated_at); 
}
 if ($model->deleted_at) 
{
 $entity->setdelete dAt($model->deleted_at); 
}
 return $entity; 
}
 /** * Convert toArray. * * @param ResourceShareEntity $entity * @return array Array */ 
    protected function entityToArray(ResourceShareEntity $entity): array 
{
 return [ 'id' => $entity->getId(), 'resource_id' => $entity->getResourceId(), 'resource_type' => $entity->getResourceType(), 'resource_name' => $entity->getResourceName(), 'share_code' => $entity->getShareCode(), 'share_type' => $entity->getShareType(), 'password' => $entity->getPassword(), 'is_password_enabled' => $entity->getIsPasswordEnabled() ? 1 : 0, 'expire_at' => $entity->getExpireAt(), 'view_count' => $entity->getViewCount(), 'created_uid' => $entity->getCreatedUid(), 'organization_code' => $entity->getOrganizationCode(), 'extra' => Json::encode($entity->getExtra() ?? []), 'is_enabled' => $entity->getIsEnabled() ? 1 : 0, 'updated_at' => $entity->getUpdatedAt(), 'deleted_at' => $entity->getdelete dAt(), 'updated_uid' => $entity->getUpdatedUid(), ]; 
}
 
}
 
