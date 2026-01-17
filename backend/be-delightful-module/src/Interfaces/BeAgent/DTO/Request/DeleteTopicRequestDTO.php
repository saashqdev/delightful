<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractDTO;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * Delete topic request DTO
 * Used to receive request parameters for deleting a topic.
 */
class DeleteTopicRequestDTO extends AbstractDTO
{
    /**
     * Task status ID (primary key)
     * String type, corresponds to the primary key of the task status table.
     */
    public string $id = '';

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|string',
        ];
    }

    /**
     * Get custom error messages for validation failures.
     */
    public function messages(): array
    {
        return [
            'id.required' => 'Task status ID cannot be empty',
            'id.string' => 'Task status ID must be a string',
        ];
    }

    /**
     * Create DTO instance from request.
     */
    public static function fromRequest(RequestInterface $request): self
    {
        $data = new self();
        $data->id = $request->input('id', '');
        return $data;
    }

    /**
     * Get task status ID (primary key).
     */
    public function getId(): string
    {
        return $this->id;
    }
}
