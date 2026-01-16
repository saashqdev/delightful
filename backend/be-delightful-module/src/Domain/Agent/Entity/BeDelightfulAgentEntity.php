<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Domain\Agent\Entity;

use App\Infrastructure\Core\AbstractEntity;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use DateTime;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BuiltinTool;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\Code;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentTool;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentToolType;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentType;
use Delightful\BeDelightful\ErrorCode\BeDelightfulErrorCode;

class BeDelightfulAgentEntity extends AbstractEntity
{
    protected ?int $id = null;

    protected string $organizationCode;

    /**
     * 唯一编码，仅在创建时生成，用作给前端的id.
     */
    protected string $code;

    /**
     * Agent名称.
     */
    protected string $name;

    /**
     * Agent描述.
     */
    protected string $description = '';

    /**
     * Agent图标.
     */
    protected array $icon = [];

    /**
     * 图标类型 1:图标 2:图片.
     */
    protected int $iconType = 1;

    /**
     * @var array<BeDelightfulAgentTool>
     */
    protected array $tools = [];

    /**
     * 系统提示词.
     * Format: {"version": "1.0.0", "structure": {"string": "prompt text"}}.
     */
    protected array $prompt = [];

    /**
     * 智能体类型.
     */
    protected BeDelightfulAgentType $type = BeDelightfulAgentType::Custom;

    /**
     * 是否启用.
     */
    protected ?bool $enabled = null;

    protected string $creator;

    protected DateTime $createdAt;

    protected string $modifier;

    protected DateTime $updatedAt;

    /**
     * Category for agent classification.
     * Values: 'frequent', 'all'.
     */
    private string $category = 'all';

    public function shouldCreate(): bool
    {
        return empty($this->code);
    }

    public function prepareForCreation(): void
    {
        if (empty($this->organizationCode)) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'organization_code']);
        }
        if (empty($this->name)) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.name']);
        }
        if (empty($this->prompt)
            || ! isset($this->prompt['version'])
            || ! isset($this->prompt['structure'])) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.prompt']);
        }
        // Check if prompt string content is empty
        if (empty(trim($this->getPromptString()))) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.prompt']);
        }
        if (empty($this->creator)) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'creator']);
        }
        if (empty($this->createdAt)) {
            $this->createdAt = new DateTime();
        }

        $this->modifier = $this->creator;
        $this->updatedAt = $this->createdAt;
        $this->code = Code::BeDelightfulAgent->gen();
        $this->enabled = $this->enabled ?? true;
        // 强制设置为自定义类型，用户创建的智能体只能是自定义类型
        $this->type = BeDelightfulAgentType::Custom;
        $this->id = null;
    }

    public function prepareForModification(BeDelightfulAgentEntity $originalEntity): void
    {
        if (empty($this->organizationCode)) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'organization_code']);
        }
        if (empty($this->name)) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.name']);
        }
        if (empty($this->prompt)
            || ! isset($this->prompt['version'])
            || ! isset($this->prompt['structure'])) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.prompt']);
        }
        // Check if prompt string content is empty
        if (empty(trim($this->getPromptString()))) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.prompt']);
        }

        // 将新值设置到原始实体上
        $originalEntity->setName($this->name);
        $originalEntity->setDescription($this->description);
        $originalEntity->setIcon($this->icon);
        $originalEntity->setTools($this->tools);
        $originalEntity->setPrompt($this->prompt);
        $originalEntity->setType($this->type);
        $originalEntity->setModifier($this->creator);
        $originalEntity->setIconType($this->iconType);

        if (isset($this->enabled)) {
            $originalEntity->setEnabled($this->enabled);
        }

        $originalEntity->setUpdatedAt(new DateTime());
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(null|int|string $id): void
    {
        if (is_string($id)) {
            $this->id = (int) $id;
        } else {
            $this->id = $id;
        }
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getIcon(): array
    {
        return $this->icon;
    }

    public function setIcon(array $icon): void
    {
        $this->icon = $icon;
    }

    public function getIconType(): int
    {
        return $this->iconType;
    }

    public function setIconType(int $iconType): void
    {
        $this->iconType = $iconType;
    }

    public function getTools(): array
    {
        $result = [];

        // 获取必填工具列表，按照 getRequiredTools 的顺序
        $requiredTools = BuiltinTool::getRequiredTools();

        // 1. 先添加必填工具（按照 getRequiredTools 的顺序）
        foreach ($requiredTools as $requiredTool) {
            $tool = new BeDelightfulAgentTool();
            $tool->setCode($requiredTool->value);
            $tool->setName($requiredTool->getToolName());
            $tool->setDescription($requiredTool->getToolDescription());
            $tool->setIcon($requiredTool->getToolIcon());
            $tool->setType(BeDelightfulAgentToolType::BuiltIn);
            $tool->setSchema(null);

            $result[$tool->getCode()] = $tool;
        }

        // 2. 再添加原始工具列表中的其他工具（跳过已存在的必填工具）
        foreach ($this->tools as $tool) {
            if ($tool->getType()->isBuiltIn()) {
                // 但是不在目前已有的内置列表中，则跳过
                if (! BuiltinTool::isValidTool($tool->getCode())) {
                    continue;
                }
            }
            if (! isset($result[$tool->getCode()])) {
                $result[$tool->getCode()] = $tool;
            }
        }

        return array_values($result);
    }

    /**
     * 获取原始工具列表（不包含自动添加的必填工具）.
     * @return array<BeDelightfulAgentTool>
     */
    public function getOriginalTools(): array
    {
        return $this->tools;
    }

    public function setTools(array $tools): void
    {
        $this->tools = [];
        foreach ($tools as $tool) {
            if ($tool instanceof BeDelightfulAgentTool) {
                $this->tools[] = $tool;
            } elseif (is_array($tool)) {
                $this->tools[] = new BeDelightfulAgentTool($tool);
            }
        }
    }

    /**
     * 添加工具，如果工具已存在则不添加.
     */
    public function addTool(BeDelightfulAgentTool $tool): void
    {
        if (array_any($this->tools, fn ($existingTool) => $existingTool->getCode() === $tool->getCode())) {
            return;
        }
        $this->tools[] = $tool;
    }

    public function getPrompt(): array
    {
        // Validate prompt format: must have version and structure keys
        if (empty($this->prompt)
            || ! isset($this->prompt['version'])
            || ! isset($this->prompt['structure'])) {
            return [];
        }

        return $this->prompt;
    }

    public function setPrompt(array $prompt): void
    {
        $this->prompt = $prompt;
    }

    /**
     * Get prompt as plain text string.
     *
     * @return string Plain text representation of the prompt
     */
    public function getPromptString(): string
    {
        $prompt = $this->getPrompt();
        if (empty($prompt)) {
            return '';
        }

        // Handle version 1.0.0 format
        if (isset($prompt['structure']['string'])) {
            return $prompt['structure']['string'];
        }

        return '';
    }

    public function getType(): BeDelightfulAgentType
    {
        return $this->type;
    }

    public function setType(int|BeDelightfulAgentType $type): void
    {
        if (is_int($type)) {
            $type = BeDelightfulAgentType::tryFrom($type);
            if ($type === null) {
                ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.invalid', ['label' => 'super_magic.agent.fields.type']);
            }
        }
        $this->type = $type;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled ?? false;
    }

    public function setEnabled(?bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): void
    {
        $this->creator = $creator;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getModifier(): string
    {
        return $this->modifier;
    }

    public function setModifier(string $modifier): void
    {
        $this->modifier = $modifier;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }
}
