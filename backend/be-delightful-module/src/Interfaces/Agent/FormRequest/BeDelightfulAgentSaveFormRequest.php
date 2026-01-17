<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\Agent\FormRequest;

use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentToolType;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentType;
use Hyperf\Validation\Request\FormRequest;

use function Hyperf\Translation\trans;

class BeDelightfulAgentSaveFormRequest extends FormRequest
{
    /**
     * 验证规则.
     */
    public function rules(): array
    {
        return [
            // 基本信息
            'id' => 'nullable|string|max:50',
            'name' => 'required|string|max:80',
            'description' => 'nullable|string|max:512',
            'icon' => 'nullable|array',
            'icon_type' => 'nullable|integer|in:1,2',

            // 智能体类型（动态获取枚举值）
            'type' => 'nullable|integer|in:' . BeDelightfulAgentType::getValidationRule(),

            // 启用状态
            'enabled' => 'nullable|boolean',

            // 系统提示词（Editor.js格式的JSON）
            'prompt_shadow' => 'nullable|string',
            'prompt' => 'required|array',

            // 工具配置
            'tools' => 'nullable|array',
            'tools.*.code' => 'required_with:tools|string|max:100',
            'tools.*.name' => 'required_with:tools|string|max:100',
            'tools.*.description' => 'nullable|string|max:2048',
            'tools.*.icon' => 'nullable|string|max:512',
            'tools.*.type' => 'required_with:tools|integer|in:' . BeDelightfulAgentToolType::getValidationRule(), // 动态获取枚举值
        ];
    }

    /**
     * 字段别名.
     */
    public function attributes(): array
    {
        return [
            'id' => trans('be_delightful.agent.fields.code'),
            'name' => trans('be_delightful.agent.fields.name'),
            'description' => trans('be_delightful.agent.fields.description'),
            'icon' => trans('be_delightful.agent.fields.icon'),
            'type' => trans('be_delightful.agent.fields.type'),
            'enabled' => trans('be_delightful.agent.fields.enabled'),
            'prompt' => trans('be_delightful.agent.fields.prompt'),
            'tools' => trans('be_delightful.agent.fields.tools'),
            'tools.*.code' => trans('be_delightful.agent.fields.tool_code'),
            'tools.*.name' => trans('be_delightful.agent.fields.tool_name'),
            'tools.*.type' => trans('be_delightful.agent.fields.tool_type'),
        ];
    }

    /**
     * 自定义验证错误消息.
     */
    public function messages(): array
    {
        return [
            // 基本字段验证
            'name.required' => trans('be_delightful.agent.validation.name_required'),
            'name.string' => trans('be_delightful.agent.validation.name_string'),
            'name.max' => trans('be_delightful.agent.validation.name_max'),
            'description.string' => trans('be_delightful.agent.validation.description_string'),
            'description.max' => trans('be_delightful.agent.validation.description_max'),
            'icon.array' => trans('be_delightful.agent.validation.icon_array'),
            'type.integer' => trans('be_delightful.agent.validation.type_integer'),
            'type.in' => trans('be_delightful.agent.validation.type_invalid'),
            'enabled.boolean' => trans('be_delightful.agent.validation.enabled_boolean'),
            'prompt.required' => trans('be_delightful.agent.validation.prompt_required'),
            'prompt.array' => trans('be_delightful.agent.validation.prompt_array'),
            'tools.array' => trans('be_delightful.agent.validation.tools_array'),

            // 工具字段验证
            'tools.*.code.required_with' => trans('be_delightful.agent.validation.tool_code_required'),
            'tools.*.code.string' => trans('be_delightful.agent.validation.tool_code_string'),
            'tools.*.code.max' => trans('be_delightful.agent.validation.tool_code_max'),
            'tools.*.name.required_with' => trans('be_delightful.agent.validation.tool_name_required'),
            'tools.*.name.string' => trans('be_delightful.agent.validation.tool_name_string'),
            'tools.*.name.max' => trans('be_delightful.agent.validation.tool_name_max'),
            'tools.*.description.string' => trans('be_delightful.agent.validation.tool_description_string'),
            'tools.*.description.max' => trans('be_delightful.agent.validation.tool_description_max'),
            'tools.*.icon.string' => trans('be_delightful.agent.validation.tool_icon_string'),
            'tools.*.icon.max' => trans('be_delightful.agent.validation.tool_icon_max'),
            'tools.*.type.required_with' => trans('be_delightful.agent.validation.tool_type_required'),
            'tools.*.type.integer' => trans('be_delightful.agent.validation.tool_type_integer'),
            'tools.*.type.in' => trans('be_delightful.agent.validation.tool_type_invalid'),
        ];
    }

    /**
     * 授权验证.
     */
    public function authorize(): bool
    {
        return true;
    }
}
