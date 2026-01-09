<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\MCP\DTO;

use App\Infrastructure\Core\AbstractDTO;
use App\Interfaces\Kernel\DTO\Traits\OperatorDTOTrait;
use App\Interfaces\Kernel\DTO\Traits\StringIdDTOTrait;

class MCPServerToolDTO extends AbstractDTO
{
    use OperatorDTOTrait;
    use StringIdDTOTrait;

    /**
     * associateMCPservicecode.
     */
    public string $mcpServerCode = '';

    /**
     * toolname.
     */
    public string $name = '';

    /**
     * tooldescription.
     */
    public string $description = '';

    /**
     * toolcome源.
     */
    public int $source = 0;

    /**
     * associatetoolcode.
     */
    public string $relCode = '';

    /**
     * associatetoolversioncode.
     */
    public string $relVersionCode = '';

    /**
     * toolversion.
     */
    public string $version = '';

    /**
     * whetherenable.
     */
    public ?bool $enabled = null;

    /**
     * toolconfiguration.
     */
    public array $options = [];

    public array $sourceVersion = [];

    /**
     * associateinformation,givefront端use,nobusinesslogic.
     */
    public ?array $relInfo = null;

    public function getMcpServerCode(): string
    {
        return $this->mcpServerCode;
    }

    public function setMcpServerCode(?string $mcpServerCode): void
    {
        $this->mcpServerCode = $mcpServerCode ?? '';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name ?? '';
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description ?? '';
    }

    public function getSource(): int
    {
        return $this->source;
    }

    public function setSource(?int $source): void
    {
        $this->source = $source ?? 0;
    }

    public function getRelCode(): string
    {
        return $this->relCode;
    }

    public function setRelCode(?string $relCode): void
    {
        $this->relCode = $relCode ?? '';
    }

    public function getRelVersionCode(): string
    {
        return $this->relVersionCode;
    }

    public function setRelVersionCode(?string $relVersionCode): void
    {
        $this->relVersionCode = $relVersionCode ?? '';
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(?string $version): void
    {
        $this->version = $version ?? '';
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(?array $options): void
    {
        $this->options = $options ?? [];
    }

    public function getSourceVersion(): array
    {
        return $this->sourceVersion;
    }

    public function setSourceVersion(array $sourceVersion): void
    {
        $this->sourceVersion = $sourceVersion;
    }

    public function getRelInfo(): ?array
    {
        return $this->relInfo;
    }

    public function setRelInfo(?array $relInfo): void
    {
        $this->relInfo = $relInfo;
    }
}
