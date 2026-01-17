<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\Agent\FormRequest;

use Hyperf\Validation\Request\FormRequest;

use function Hyperf\Translation\trans;

class BeDelightfulAgentQueryFormRequest extends FormRequest
{
    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            // Pagination parameters
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:1000',

            // Search conditions - only fields that actually exist in BeDelightfulAgentQuery
            'name' => 'nullable|string|max:80',
            'enabled' => 'nullable|boolean',
            'codes' => 'nullable|array',
            'codes.*' => 'string|max:50',
            'creator_id' => 'nullable|string|max:40',
        ];
    }

    /**
     * Field aliases.
     */
    public function attributes(): array
    {
        return [
            'page' => trans('be_delightful.agent.fields.page'),
            'page_size' => trans('be_delightful.agent.fields.page_size'),
            'name' => trans('be_delightful.agent.fields.name'),
            'enabled' => trans('be_delightful.agent.fields.enabled'),
            'codes' => trans('be_delightful.agent.fields.codes'),
            'codes.*' => trans('be_delightful.agent.fields.code'),
            'creator_id' => trans('be_delightful.agent.fields.creator_id'),
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            // Pagination parameter validation
            'page.integer' => trans('be_delightful.agent.validation.page_integer'),
            'page.min' => trans('be_delightful.agent.validation.page_min'),
            'page_size.integer' => trans('be_delightful.agent.validation.page_size_integer'),
            'page_size.min' => trans('be_delightful.agent.validation.page_size_min'),
            'page_size.max' => trans('be_delightful.agent.validation.page_size_max'),

            // Search condition validation
            'name.string' => trans('be_delightful.agent.validation.name_string'),
            'name.max' => trans('be_delightful.agent.validation.name_max'),
            'enabled.boolean' => trans('be_delightful.agent.validation.enabled_boolean'),
            'codes.array' => trans('be_delightful.agent.validation.codes_array'),
            'codes.*.string' => trans('be_delightful.agent.validation.code_string'),
            'codes.*.max' => trans('be_delightful.agent.validation.code_max'),
            'creator_id.string' => trans('be_delightful.agent.validation.creator_id_string'),
            'creator_id.max' => trans('be_delightful.agent.validation.creator_id_max'),
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
