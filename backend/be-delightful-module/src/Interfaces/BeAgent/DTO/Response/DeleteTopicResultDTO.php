<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

/**
 * Delete topic result DTO
 * Used to encapsulate the return data of delete topic operation.
 */
class DeleteTopicResultDTO extends AbstractDTO
{
    /**
     * Deleted task status ID (primary key)
     * String type.
     */
    public string $id;

    /**
     * Constructor.
     */
    public function __construct(?array $data = null)
    {
        parent::__construct($data);
    }

    /**
     * Create DTO from task status ID.
     *
     * @param int $id Task status ID (primary key)
     */
    public static function fromId(int $id): self
    {
        $dto = new self();
        $dto->id = (string) $id;
        return $dto;
    }

    /**
     * Get task status ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set task status ID.
     *
     * @param int $id Task status ID (primary key)
     */
    public function setId(int $id): self
    {
        $this->id = (string) $id;
        return $this;
    }
}
