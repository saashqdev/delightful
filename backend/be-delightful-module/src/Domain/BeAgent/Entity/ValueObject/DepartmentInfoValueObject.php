<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Department information value object.
 */
class DepartmentInfoValueObject
{
    /**
     * Constructor.
     *
     * @param string $id Department ID
     * @param string $name Department name
     * @param string $path Department path
     */
    public function __construct(
        private string $id = '',
        private string $name = '',
        private string $path = ''
    ) {
    }

    /**
     * Create department information object from array.
     *
     * @param array $data Department information array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            $data['name'] ?? '',
            $data['path'] ?? ''
        );
    }

    /**
     * Convert to array.
     *
     * @return array Department information array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
        ];
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    // Withers for immutability
    public function withId(string $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;
        return $clone;
    }

    public function withPath(string $path): self
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    /**
     * Check if department information is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->id) && empty($this->name) && empty($this->path);
    }

    /**
     * Check if department information is valid.
     */
    public function isValid(): bool
    {
        return ! empty($this->id) && ! empty($this->name);
    }
}
