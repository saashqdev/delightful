<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Entity;

use App\Infrastructure\Core\AbstractEntity;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use DateTime;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\Builtintool ;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\Code;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgenttool ;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgenttool Type;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentType;
use Delightful\BeDelightful\ErrorCode\BeDelightfulErrorCode;

class BeDelightfulAgentEntity extends AbstractEntity 
{
 protected ?int $id = null; 
    protected string $organizationCode; /** * Encodeonly AtCreateGenerate give Frontendid. */ 
    protected string $code; /** * AgentName. */ 
    protected string $name; /** * AgentDescription. */ 
    protected string $description = ''; /** * AgentIcon. */ 
    protected array $icon = []; /** * IconType 1:Icon 2:Image. */ 
    protected int $iconType = 1; /** * @var array<BeDelightfulAgenttool > */ 
    protected array $tools = []; /** * SystemNotice. * Format: 
{
 version : 1.0.0 , structure : 
{
 string : prompt text 
}

}
. */ 
    protected array $prompt = []; /** * Type. */ 
    protected BeDelightfulAgentType $type = BeDelightfulAgentType::Custom; /** * whether Enabled. */ protected ?bool $enabled = null; 
    protected string $creator; 
    protected DateTime $createdAt; 
    protected string $modifier; 
    protected DateTime $updatedAt; /** * Category for agent classification. * Values: 'frequent', 'all'. */ 
    private string $category = 'all'; 
    public function shouldCreate(): bool 
{
 return empty($this->code); 
}
 
    public function prepareForCreation(): void 
{
 if (empty($this->organizationCode)) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'organization_code']); 
}
 if (empty($this->name)) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.name']); 
}
 if (empty($this->prompt) || ! isset($this->prompt['version']) || ! isset($this->prompt['structure'])) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.prompt']); 
}
 // check if prompt string content is empty if (empty(trim($this->getPromptString()))) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.prompt']); 
}
 if (empty($this->creator)) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'creator']); 
}
 if (empty($this->createdAt)) 
{
 $this->createdAt = new DateTime(); 
}
 $this->modifier = $this->creator; $this->updatedAt = $this->createdAt; $this->code = Code::BeDelightfulAgent->gen(); $this->enabled = $this->enabled ?? true; // ForceSet as CustomTypeuser Createyes CustomType $this->type = BeDelightfulAgentType::Custom; $this->id = null; 
}
 
    public function prepareForModification(BeDelightfulAgentEntity $originalEntity): void 
{
 if (empty($this->organizationCode)) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'organization_code']); 
}
 if (empty($this->name)) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.name']); 
}
 if (empty($this->prompt) || ! isset($this->prompt['version']) || ! isset($this->prompt['structure'])) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.prompt']); 
}
 // check if prompt string content is empty if (empty(trim($this->getPromptString()))) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'super_magic.agent.fields.prompt']); 
}
 // NewValueSet original $originalEntity->setName($this->name); $originalEntity->setDescription($this->description); $originalEntity->setIcon($this->icon); $originalEntity->settool s($this->tools); $originalEntity->setPrompt($this->prompt); $originalEntity->setType($this->type); $originalEntity->setModifier($this->creator); $originalEntity->setIconType($this->iconType); if (isset($this->enabled)) 
{
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
 if (is_string($id)) 
{
 $this->id = (int) $id; 
}
 else 
{
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
 
    public function gettool s(): array 
{
 $result = []; // GetRequiredtool list follow getRequiredtool s order $requiredtool s = Builtintool ::getRequiredtool s(); // 1. First add required tools (follow getRequiredtool s order) foreach ($requiredtool s as $requiredtool ) 
{
 $tool = new BeDelightfulAgenttool (); $tool->setCode($requiredtool ->value); $tool->setName($requiredtool ->gettool Name()); $tool->setDescription($requiredtool ->gettool Description()); $tool->setIcon($requiredtool ->gettool Icon()); $tool->setType(BeDelightfulAgenttool Type::BuiltIn); $tool->setSchema(null); $result[$tool->getCode()] = $tool; 
}
 // 2. Addoriginal tool list in tool SkipAlready existsRequiredtool  foreach ($this->tools as $tool) 
{
 if ($tool->getType()->isBuiltIn()) 
{
 // yes AtHaveBuilt-inlist in Skip if (! Builtintool ::isValidtool ($tool->getCode())) 
{
 continue; 
}
 
}
 if (! isset($result[$tool->getCode()])) 
{
 $result[$tool->getCode()] = $tool; 
}
 
}
 return array_values($result); 
}
 /** * Getoriginal tool list Not containautomatic AddRequiredtool . * @return array<BeDelightfulAgenttool > */ 
    public function getOriginaltool s(): array 
{
 return $this->tools; 
}
 
    public function settool s(array $tools): void 
{
 $this->tools = []; foreach ($tools as $tool) 
{
 if ($tool instanceof BeDelightfulAgenttool ) 
{
 $this->tools[] = $tool; 
}
 elseif (is_array($tool)) 
{
 $this->tools[] = new BeDelightfulAgenttool ($tool); 
}
 
}
 
}
 /** * Addtool Iftool Already existsAdd. */ 
    public function addtool (BeDelightfulAgenttool $tool): void 
{
 if (array_any($this->tools, fn ($existingtool ) => $existingtool ->getCode() === $tool->getCode())) 
{
 return; 
}
 $this->tools[] = $tool; 
}
 
    public function getPrompt(): array 
{
 // Validate prompt format: must have version and structure keys if (empty($this->prompt) || ! isset($this->prompt['version']) || ! isset($this->prompt['structure'])) 
{
 return []; 
}
 return $this->prompt; 
}
 
    public function setPrompt(array $prompt): void 
{
 $this->prompt = $prompt; 
}
 /** * Get prompt as plain text string. * * @return string Plain text representation of the prompt */ 
    public function getPromptString(): string 
{
 $prompt = $this->getPrompt(); if (empty($prompt)) 
{
 return ''; 
}
 // Handle version 1.0.0 format if (isset($prompt['structure']['string'])) 
{
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
 if (is_int($type)) 
{
 $type = BeDelightfulAgentType::tryFrom($type); if ($type === null) 
{
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
 
    public function getcreator (): string 
{
 return $this->creator; 
}
 
    public function setcreator (string $creator): void 
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
 
