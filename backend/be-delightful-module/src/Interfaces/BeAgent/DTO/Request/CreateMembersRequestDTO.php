<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;
use function Hyperf\Translation\__;
/** * CreateItemMemberRequestDTO. * * CreateItemMemberRequestParameterValidate * InheritanceAbstractRequestDTOautomatic SupportParameterValidate TypeConvert */

class CreateMembersRequestDTO extends AbstractRequestDTO 
{
 /** @var array MemberDatalist */ 
    public array $members = []; 
    public function getMembers(): array 
{
 return $this->members; 
}
 
    public function setMembers(array $members): void 
{
 $this->members = $members; 
}
 /** * Validate Rule. */ 
    protected 
    static function getHyperfValidate Rules(): array 
{
 return [ 'members' => 'required|array|min:1|max:500', 'members.*.target_type' => 'required|string', 'members.*.target_id' => 'required|string|max:128', 'members.*.role' => 'required|string|in:viewer,editor,manage', ]; 
}
 /** * Validate errorMessageSupport. */ 
    protected 
    static function getHyperfValidate Message(): array 
{
 return [ 'members.required' => __('validation.project.members.required'), 'members.array' => __('validation.project.members.array'), 'members.min' => __('validation.project.members.min'), 'members.max' => __('validation.project.members.max'), 'members.*.target_type.required' => __('validation.project.target_type.required'), 'members.*.target_type.string' => __('validation.project.target_type.string'), 'members.*.target_type.in' => __('validation.project.target_type.in'), 'members.*.target_id.required' => __('validation.project.target_id.required'), 'members.*.target_id.string' => __('validation.project.target_id.string'), 'members.*.target_id.max' => __('validation.project.target_id.max'), 'members.*.role.required' => __('validation.project.permission.required'), 'members.*.role.string' => __('validation.project.permission.string'), 'members.*.role.in' => __('validation.project.permission.in'), ]; 
}
 
}
 
