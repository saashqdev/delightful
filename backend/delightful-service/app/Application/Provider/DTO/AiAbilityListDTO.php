<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Provider\DTO;

/**
 * AIcancapabilitycolumntableDTO.
 */
class AiAbilityListDTO
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $description,
        public int $status,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
