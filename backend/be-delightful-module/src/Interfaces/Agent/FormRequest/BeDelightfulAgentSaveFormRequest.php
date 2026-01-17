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
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            // Basic information
            'id' => 'nullable|string|max:50',
            'name' => 'required|string|max:80',
            'description' => 'nullable|string|max:512',
            'icon' => 'nullable|array',
            'icon_type' => 'nullable|integer|in:1,2',

            // Agent type (dynamically get enum values)
            'type' => 'nullable|integer|in:' . BeDelightfulAgentType::getValidationRule(),

            // Enable status
            'enabled' => 'nullable|boolean',

            // System prompt (Editor.js format JSON)
            'prompt_shadow' => 'nullable|string',
            'prompt' => 'required|array',

            // Tools configuration
            'tools' => 'nullable|array',
            'tools.*.code' => 'required_with:tools|string|max:100',
            'tools.*.name' => 'required_with:tools|string|max:100',
            'tools.*.description' => 'nullable|string|max:2048',
            'tools.*.icon' => 'nullable|string|max:512',
            'tools.*.type' => 'required_with:tools|integer|in:' . BeDelightfulAgentToolType::getValidationRule(), // Dynamically get enum values
        ];
    }

    /**
     * Field aliases.
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
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            // Basic field validation
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

            // Tool field validation
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
     * Authorization check.
     */
    public function authorize(): bool
    {
        return true;
    }
}
