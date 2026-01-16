<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\Application\LongTermMemory\Service\LongTermMemoryAppService;
use App\Domain\LongTermMemory\DTO\CreateMemoryDTO;
use App\Domain\LongTermMemory\DTO\UpdateMemoryDTO;
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryStatus;
use App\Domain\LongTermMemory\Entity\ValueObject\MemoryType;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\ShadowCode\ShadowCode;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\AgentConstant;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MessageMetadata;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validate \Contract\ValidatorFactoryInterface;
use InvalidArgumentException;
use function Hyperf\Translation\trans;
#[ApiResponse('low_code')]

class SuperAgentMemoryApi extends AbstractApi 
{
 
    public function __construct( 
    protected RequestInterface $request, 
    protected ValidatorFactoryInterface $validator, 
    protected LongTermMemoryAppService $longTermMemoryAppService, ) 
{
 parent::__construct($request); 
}
 /** * Creatememory . */ 
    public function createMemory(): array 
{
 // sandbox Token $this->validateSandboxToken(); $requestData = $this->getRequestData(); $rules = [ 'explanation' => 'required|string', 'memory' => 'required|string', 'tags' => 'array', 'metadata' => 'required|array', 'immediate_effect' => 'boolean|nullable', 'project_id' => 'nullable|integer|string', ]; $validatedParams = $this->checkParams($requestData, $rules); $metadata = $this->parseMetadata($validatedParams['metadata']); // According to immediate_effect parameter decide memory StatusContentSet $immediateEffect = (bool) ($validatedParams['immediate_effect'] ?? false); if ($immediateEffect) 
{
 // memory Contentdirectly contentStatusas active $content = $validatedParams['memory']; $pendingContent = null; $status = MemoryStatus::ACTIVE->value; $enabled = true; // Memory for active status is enabled by default 
}
 else 
{
 // Defaultbehavior is memory ContentpendingContentStatusas pending $content = ''; $pendingContent = $validatedParams['memory']; $status = MemoryStatus::PENDING->value; $enabled = false; // Memory for pending status is disabled by default 
}
 $dto = new CreateMemoryDTO([ 'content' => $content, 'pendingContent' => $pendingContent, 'explanation' => $validatedParams['explanation'], 'memoryType' => MemoryType::MANUAL_INPUT->value, 'status' => $status, 'enabled' => $enabled, 'tags' => $validatedParams['tags'] ?? [], 'orgId' => $metadata->getOrganizationCode(), 'appId' => AgentConstant::SUPER_MAGIC_CODE, // Item id CannotFrom $metadata Getas Parameteryes memory yes Itemyes Global 'projectId' => isset($validatedParams['project_id']) ? (string) $validatedParams['project_id'] : null, 'userId' => $metadata->getuser Id(), 'expiresAt' => null, ]); $memoryId = $this->longTermMemoryAppService->createMemory($dto); return ['memory_id' => $memoryId, 'success' => true]; 
}
 /** * AgentUpdatememory Core. */ 
    public function agentUpdateMemory(string $id): array 
{
 // sandbox Token $this->validateSandboxToken(); $requestData = $this->getRequestData(); $rules = [ 'explanation' => 'string', 'memory' => 'string', 'tags' => 'array', 'metadata' => 'required|array', ]; $validatedParams = $this->checkParams($requestData, $rules); $metadata = $this->parseMetadata($validatedParams['metadata']); // check permission $this->checkMemorypermission ($id, $metadata); // BuildUpdateDTOStatusConvertServiceautomatic process $dto = new UpdateMemoryDTO([ 'pendingContent' => $validatedParams['memory'] ?? null, 'explanation' => $validatedParams['explanation'] ?? null, 'tags' => $validatedParams['tags'] ?? null, 'metadata' => $validatedParams['metadata'] ?? null, ]); $this->longTermMemoryAppService->updateMemory($id, $dto); return ['success' => true]; 
}
 /** * delete memory . */ 
    public function deleteMemory(string $id): array 
{
 // sandbox Token $this->validateSandboxToken(); $requestData = $this->getRequestData(); $rules = [ 'metadata' => 'required|array', ]; $validatedParams = $this->checkParams($requestData, $rules); $metadata = $this->parseMetadata($validatedParams['metadata']); // check permission $this->checkMemorypermission ($id, $metadata); $this->longTermMemoryAppService->deleteMemory($id); return [ 'success' => true, 'message' => trans('long_term_memory.api.memory_deleted_successfully'), ]; 
}
 /** * RequestParameter. * * @throws InvalidArgumentException */ 
    protected function checkParams(array $params, array $rules): array 
{
 $validator = $this->validator->make($params, $rules); if ($validator->fails()) 
{
 throw new InvalidArgumentException(trans('long_term_memory.api.parameter_validation_failed', ['errors' => implode(', ', $validator->errors()->all())])); 
}
 return $validator->validated(); 
}
 /** * GetRequestDataprocess . */ 
    private function getRequestData(): array 
{
 // Viewwhether $isConfusion = $this->request->input('obfuscated', false); if ($isConfusion) 
{
 // process $rawData = ShadowCode::unShadow($this->request->input('data', '')); return json_decode($rawData, true); 
}
 return $this->request->all(); 
}
 /** * Parse metadata. */ 
    private function parseMetadata(array $metadataArray): MessageMetadata 
{
 return MessageMetadata::fromArray($metadataArray); 
}
 /** * check memory permission . */ 
    private function checkMemorypermission (string $memoryId, MessageMetadata $metadata): void 
{
 if (! $this->longTermMemoryAppService->isMemoryBelongTouser ( $memoryId, $metadata->getOrganizationCode(), AgentConstant::SUPER_MAGIC_CODE, $metadata->getuser Id() )) 
{
 ExceptionBuilder::throw(GenericErrorCode::AccessDenied, trans('long_term_memory.api.memory_not_belong_to_user')); 
}
 
}
 
}
 
