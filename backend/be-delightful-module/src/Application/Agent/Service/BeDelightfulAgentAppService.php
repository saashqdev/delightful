<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\Agent\Service;

use App\Application\Contact\user Setting\user SettingKey;
use App\Application\Flow\execute Manager\NodeRunner\LLM\tool sExecutor;
use App\Application\Flow\Service\MagicFlowexecute AppService;
use App\Domain\Contact\Entity\Magicuser SettingEntity;
use App\Domain\Contact\Service\Magicuser SettingDomainService;
use App\Domain\Mode\Entity\ModeEntity;
use App\Domain\Mode\Entity\Valuequery \Modequery ;
use App\Domain\Mode\Service\ModeDomainService;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\DTO\MagicFlowApiChatDTO;
use DateTime;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\query \BeDelightfulAgentquery ;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentDataIsolation;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentType;
use Delightful\BeDelightful\ErrorCode\BeDelightfulErrorCode;
use Hyperf\Di\Annotation\Inject;
use Qbhy\HyperfAuth\Authenticatable;

class BeDelightfulAgentAppService extends AbstractBeDelightfulAppService 
{
 #[Inject] 
    protected Magicuser SettingDomainService $magicuser SettingDomainService; #[Inject] 
    protected ModeDomainService $modeDomainService; 
    public function show(Authenticatable $authorization, string $code, bool $withtool Schema = false): BeDelightfulAgentEntity 
{
 $dataIsolation = $this->createBeDelightfulDataIsolation($authorization); $flowDataIsolation = $this->createFlowDataIsolation($authorization); $agent = $this->BeDelightfulAgentDomainService->getByCodeWithException($dataIsolation, $code); if ($withtool Schema) 
{
 $remotetool Codes = []; foreach ($agent->gettool s() as $tool) 
{
 if ($tool->getType()->isRemote()) 
{
 $remotetool Codes[] = $tool->getCode(); 
}
 
}
 // Gettool $remotetool s = tool sExecutor::gettool Flows($flowDataIsolation, $remotetool Codes, true); foreach ($agent->gettool s() as $tool) 
{
 $remotetool = $remotetool s[$tool->getCode()] ?? null; if ($remotetool ) 
{
 $tool->setSchema($remotetool ->getInput()->getForm()?->getForm()->toJsonSchema()); 
}
 
}
 
}
 return $agent; 
}
 /** * @return array
{
frequent: array<BeDelightfulAgentEntity>, all: array<BeDelightfulAgentEntity>, total: int
}
 */ 
    public function queries(Authenticatable $authorization, BeDelightfulAgentquery $query, Page $page): array 
{
 $dataIsolation = $this->createBeDelightfulDataIsolation($authorization); // query Fullquery $query->setcreator Id($authorization->getId()); $page->disable(); $query->setSelect(['id', 'code', 'name', 'description', 'icon', 'icon_type']); // Only select necessary fields for list $result = $this->BeDelightfulAgentDomainService->queries($dataIsolation, $query, $page); // MergeBuilt-inModel $builtinAgents = $this->getBuiltinAgent($dataIsolation); if (! $page->isEnabled()) 
{
 $result['list'] = array_merge($builtinAgents, $result['list']); $result['total'] += count($builtinAgents); 
}
 // According touser ColumnConfigurationPairResultRowCategory $orderConfig = $this->getOrderConfig($authorization); return $this->categorizeAgents($result['list'], $result['total'], $orderConfig); 
}
 
    public function save(Authenticatable $authorization, BeDelightfulAgentEntity $entity): BeDelightfulAgentEntity 
{
 $dataIsolation = $this->createBeDelightfulDataIsolation($authorization); return $this->BeDelightfulAgentDomainService->save($dataIsolation, $entity); 
}
 
    public function delete(Authenticatable $authorization, string $code): bool 
{
 $dataIsolation = $this->createBeDelightfulDataIsolation($authorization); return $this->BeDelightfulAgentDomainService->delete($dataIsolation, $code); 
}
 
    public function enable(Authenticatable $authorization, string $code): BeDelightfulAgentEntity 
{
 $dataIsolation = $this->createBeDelightfulDataIsolation($authorization); return $this->BeDelightfulAgentDomainService->enable($dataIsolation, $code); 
}
 
    public function disable(Authenticatable $authorization, string $code): BeDelightfulAgentEntity 
{
 $dataIsolation = $this->createBeDelightfulDataIsolation($authorization); return $this->BeDelightfulAgentDomainService->disable($dataIsolation, $code); 
}
 /** * SaveColumnConfiguration. * @param array
{
frequent: array<string>, all: array<string>
}
 $orderConfig */ 
    public function saveOrderConfig(Authenticatable $authorization, array $orderConfig): Magicuser SettingEntity 
{
 $dataIsolation = $this->createContactDataIsolation($authorization); $entity = new Magicuser SettingEntity(); $entity->setKey(user SettingKey::BeDelightfulAgentSort->value); $entity->setValue($orderConfig); return $this->magicuser SettingDomainService->save($dataIsolation, $entity); 
}
 /** * GetColumnConfiguration. * @return null|array
{
frequent: array<string>, all: array<string>
}
 */ 
    public function getOrderConfig(Authenticatable $authorization): ?array 
{
 $dataIsolation = $this->createContactDataIsolation($authorization); $setting = $this->magicuser SettingDomainService->get($dataIsolation, user SettingKey::BeDelightfulAgentSort->value); return $setting?->getValue(); 
}
 
    public function executetool (Authenticatable $authorization, array $params): array 
{
 $toolCode = $params['code'] ?? ''; $arguments = $params['arguments'] ?? []; if (empty($toolCode)) 
{
 ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'code']); 
}
 $flowDataIsolation = $this->createFlowDataIsolation($authorization); $toolFlow = tool sExecutor::gettool Flows($flowDataIsolation, [$toolCode])[0] ?? null; if (! $toolFlow || ! $toolFlow->isEnabled()) 
{
 $label = $toolFlow ? $toolFlow->getName() : $toolCode; ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.disabled', ['label' => $label]); 
}
 $apiChatDTO = new MagicFlowApiChatDTO(); $apiChatDTO->setParams($arguments); $apiChatDTO->setFlowCode($toolFlow->getCode()); $apiChatDTO->setFlowVersionCode($toolFlow->getVersionCode()); $apiChatDTO->setMessage('super_magic_tool_call'); return di(MagicFlowexecute AppService::class)->apiParamCallByRemotetool ( $flowDataIsolation, $apiChatDTO, 'super_magic_tool_call' ); 
}
 /** * list Followuser ConfigurationCategoryas frequentall. */ 
    private function categorizeAgents(array $agents, int $total, ?array $orderConfig): array 
{
 // IfDon't haveuser ConfigurationUsingDefaultConfigurationBuilt-in6as frequent if (empty($orderConfig)) 
{
 $orderConfig = $this->getDefaultOrderConfig($agents); 
}
 $frequentCodes = $orderConfig['frequent'] ?? []; $allOrder = $orderConfig['all'] ?? []; // CreatecodeentityMap $agentMap = []; foreach ($agents as $agent) 
{
 $agentMap[$agent->getCode()] = $agent; 
}
 // Buildfrequentlist $frequent = []; foreach ($frequentCodes as $code) 
{
 if (isset($agentMap[$code])) 
{
 $agentMap[$code]->setCategory('frequent'); $frequent[] = $agentMap[$code]; 
}
 
}
 // Buildalllist Excludefrequentin  $all = []; $frequentCodesSet = array_flip($frequentCodes); // IfHaveSortConfigurationConfigurationSort if (! empty($allOrder)) 
{
 foreach ($allOrder as $code) 
{
 if (isset($agentMap[$code]) && ! isset($frequentCodesSet[$code])) 
{
 $agentMap[$code]->setCategory('all'); $all[] = $agentMap[$code]; 
}
 
}
 // AddAtSortConfigurationAI agent in foreach ($agents as $agent) 
{
 $code = $agent->getCode(); if (! in_array($code, $allOrder) && ! isset($frequentCodesSet[$code])) 
{
 $agent->setCategory('all'); $all[] = $agent; 
}
 
}
 
}
 else 
{
 // Don't haveSortConfigurationdirectly Filterfrequent foreach ($agents as $agent) 
{
 if (! isset($frequentCodesSet[$agent->getCode()])) 
{
 $agent->setCategory('all'); $all[] = $agent; 
}
 
}
 
}
 return [ 'frequent' => $frequent, 'all' => $all, 'total' => $total, ]; 
}
 /** * GetDefaultSortConfigurationBuilt-in6as frequent. * @param array<BeDelightfulAgentEntity> $agents */ 
    private function getDefaultOrderConfig(array $agents): array 
{
 $builtinCodes = []; $customCodes = []; foreach ($agents as $agent) 
{
 if ($agent->getType()->isBuiltIn()) 
{
 $builtinCodes[] = $agent->getCode(); 
}
 else 
{
 $customCodes[] = $agent->getCode(); 
}
 
}
 // Built-in6as frequent $frequent = array_slice($builtinCodes, 0, 6); // allincluding AllBuilt-in+Custom $all = array_merge($builtinCodes, $customCodes); return [ 'frequent' => $frequent, 'all' => $all, ]; 
}
 /** * @return array<BeDelightfulAgentEntity> */ 
    private function getBuiltinAgent(BeDelightfulAgentDataIsolation $BeDelightfulAgentDataIsolation): array 
{
 $modeDataIsolation = $this->createModeDataIsolation($BeDelightfulAgentDataIsolation); $modeDataIsolation->setOnlyOfficialOrganization(true); $query = new Modequery (excludeDefault: true, status: true); $modesResult = $this->modeDomainService->getModes($modeDataIsolation, $query, Page::createNoPage()); $list = []; foreach ($modesResult['list'] as $mode) 
{
 $list[] = $this->createBuiltinAgentEntityByMode($BeDelightfulAgentDataIsolation, $mode); 
}
 return $list; 
}
 
    private function createBuiltinAgentEntityByMode(BeDelightfulAgentDataIsolation $BeDelightfulAgentDataIsolation, ModeEntity $modeEntity): BeDelightfulAgentEntity 
{
 $entity = new BeDelightfulAgentEntity(); // Set basic info $entity->setOrganizationCode($BeDelightfulAgentDataIsolation->getcurrent OrganizationCode()); $entity->setCode($modeEntity->getIdentifier()); $entity->setName($modeEntity->getName()); $entity->setDescription($modeEntity->getPlaceholder()); $entity->setIcon([ 'url' => $modeEntity->getIconUrl(), 'type' => $modeEntity->getIcon(), 'color' => $modeEntity->getColor(), ]); $entity->setIconType($modeEntity->getIconType()); $entity->setType(BeDelightfulAgentType::Built_In); $entity->setEnabled(true); $entity->setPrompt([]); $entity->settool s([]); // Set SystemCreateinfo $entity->setcreator ('system'); $entity->setCreatedAt(new DateTime()); $entity->setModifier('system'); $entity->setUpdatedAt(new DateTime()); return $entity; 
}
 
}
 
