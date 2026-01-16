<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Share\DTO\Request;

use App\Infrastructure\Core\AbstractDTO;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Constant\ShareAccessType;
use Hyperf\HttpServer\Contract\RequestInterface;
/** * CreateShareRequestDTO. */

class CreateShareRequestDTO extends AbstractDTO 
{
 /** * ResourceID. */ 
    public string $resourceId = ''; /** * ResourceType. */ 
    public int $resourceType = 0; /** * ShareType. */ 
    public int $shareType = 0; /** * Password */ public ?string $password = null; /** * . */ public ?int $expireDays = null; /** * TargetIDlist . */ 
    public array $targetIds = []; /** * FromRequestin CreateDTO. */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 $dto = new self(); $dto->resourceId = (string) $request->input('resource_id', ''); $dto->resourceType = (int) $request->input('resource_type', 0); $dto->shareType = (int) $request->input('share_type', 0); $dto->password = $request->has('pwd') ? (string) $request->input('pwd') : null; $dto->expireDays = $request->has('expire_days') ? (int) $request->input('expire_days') : null; $dto->targetIds = $request->input('target_ids', []); return $dto; 
}
 /** * GetResourceID. */ 
    public function getResourceId(): string 
{
 return $this->resourceId; 
}
 /** * GetResourceType. */ 
    public function getResourceType(): ResourceType 
{
 return ResourceType::from($this->resourceType); 
}
 /** * GetShareType. */ 
    public function getShareType(): ShareAccessType 
{
 return ShareAccessType::from($this->shareType); 
}
 /** * GetPassword */ 
    public function getPassword(): ?string 
{
 return $this->password; 
}
 /** * Get. */ 
    public function getExpireDays(): ?int 
{
 return $this->expireDays; 
}
 /** * GetTargetIDlist . */ 
    public function getTargetIds(): array 
{
 return $this->targetIds; 
}
 /** * BuildValidate Rule. */ 
    public function rules(): array 
{
 return [ 'resource_id' => 'required|string|max:64', 'resource_type' => 'required|integer|min:1', 'share_type' => 'required|integer|min:1|max:4', 'password' => 'nullable|string|min:4|max:32', 'expire_days' => 'nullable|integer|min:1|max:365', 'target_ids' => 'nullable|array', 'target_ids.*.type' => 'required_with:target_ids|integer|min:1|max:3', 'target_ids.*.id' => 'required_with:target_ids|string|max:64', ]; 
}
 /** * GetValidate errorMessage. */ 
    public function messages(): array 
{
 return [ 'resource_id.required' => 'ResourceIDCannot be empty', 'resource_type.required' => 'ResourceTypeCannot be empty', 'share_type.required' => 'ShareTypeCannot be empty', 'password.min' => 'PasswordLengthas 4', 'expire_days.min' => 'Validas 1', 'expire_days.max' => 'Validat most as 365', ]; 
}
 /** * PropertyName. */ 
    public function attributes(): array 
{
 return [ 'resource_id' => 'ResourceID', 'resource_type' => 'ResourceType', 'share_type' => 'ShareType', 'password' => 'Password', 'expire_days' => '', 'target_ids' => 'TargetIDlist ', ]; 
}
 
}
 
