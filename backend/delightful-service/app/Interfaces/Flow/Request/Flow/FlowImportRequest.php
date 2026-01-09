<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Request\Flow;

use Hyperf\Validation\Request\FormRequest;

class FlowImportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'import_data' => 'required|array',
            'import_data.main_flow' => 'required|array',
            'import_data.main_flow.code' => 'required|string',
            'import_data.main_flow.name' => 'required|string',
            'import_data.main_flow.nodes' => 'required|array',
            'import_data.main_flow.edges' => 'required|array',
            'import_data.sub_flows' => 'array',
            'import_data.tool_flows' => 'array',
            'import_data.tool_sets' => 'array',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'import_data' => 'importdata',
            'import_data.main_flow' => 'mainprocess',
            'import_data.main_flow.code' => 'mainprocessencoding',
            'import_data.main_flow.name' => 'mainprocessname',
            'import_data.main_flow.nodes' => 'mainprocesssectionpoint',
            'import_data.main_flow.edges' => 'mainprocesssidefate',
            'import_data.sub_flows' => 'childprocess',
            'import_data.tool_flows' => 'toolprocess',
            'import_data.tool_sets' => 'toolcollection',
        ];
    }
}
