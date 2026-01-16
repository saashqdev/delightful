<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\Agent\Service;

use DateTime;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentOptimizationType;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgenttool ;
use Hyperf\Odin\Api\Response\ChatCompletionResponse;
use Hyperf\Odin\Message\AssistantMessage;
use Qbhy\HyperfAuth\Authenticatable;

class BeDelightfulAgentAiOptimizeAppService extends AbstractBeDelightfulAppService 
{
 
    public function optimizeAgent(Authenticatable $authorization, BeDelightfulAgentOptimizationType $optimizationType, BeDelightfulAgentEntity $agentEntity, array $availabletool s): BeDelightfulAgentEntity 
{
 $dataIsolation = $this->createBeDelightfulDataIsolation($authorization); $agentEntity->setcreator ($dataIsolation->getcurrent user Id()); $agentEntity->setCreatedAt(new DateTime()); $agentEntity->setModifier($dataIsolation->getcurrent user Id()); $agentEntity->setUpdatedAt(new DateTime()); $agentEntity->setOrganizationCode($dataIsolation->getcurrent OrganizationCode()); if ($optimizationType->isNone()) 
{
 $this->logger->info('No optimization type selected, returning original entity.'); return $agentEntity; 
}
 // check optimize ConditionMeetConditiondirectly Return if ($this->checkOptimizationPreconditions($optimizationType, $agentEntity)) 
{
 $this->logger->info('Optimization preconditions not met, returning original entity.'); return $agentEntity; 
}
 // 1. Get optimize agent (specified file path) $agentFilePath = SUPER_MAGIC_MODULE_PATH . '/src/Application/Agent/MicroAgent/AgentOptimizer.agent.yaml'; // @phpstan-ignore-line $optimizerAgent = $this->microAgentFactory->getAgent('BeDelightfulAgentOptimizer', $agentFilePath); // 2. Set optimize tool $optimizerAgent->settool s($this->getAgentOptimizertool s()); // 3. Builduser Notice $userPrompt = $this->builduser Prompt($optimizationType, $agentEntity, $availabletool s); // 4. call AI Rowoptimize $response = $optimizerAgent->easyCall( organizationCode: $dataIsolation->getcurrent OrganizationCode(), userPrompt: $userPrompt, businessParams: [ 'organization_id' => $dataIsolation->getcurrent OrganizationCode(), 'user_id' => $dataIsolation->getcurrent user Id(), 'source_id' => 'super_magic_agent_optimizer', ] ); // 5. tool call ResultUpdate return $this->extracttool CallResult($response, $agentEntity, $availabletool s); 
}
 
    private function getAgentOptimizertool s(): array 
{
 return [ // 1. optimize NameDescriptiontool [ 'type' => 'function', 'function' => [ 'name' => BeDelightfulAgentOptimizationType::OptimizeNameDescription->value, 'description' => 'According toContentas optimize Description', 'parameters' => [ 'type' => 'object', 'properties' => [ 'name' => [ 'type' => 'string', 'description' => 'NameMust be2-10CharacterName', ], 'description' => [ 'type' => 'string', 'description' => 'Description20-100CharacterDescription', ], ], 'required' => ['name', 'description'], ], ], ], // 2. optimize Contenttool [ 'type' => 'function', 'function' => [ 'name' => BeDelightfulAgentOptimizationType::OptimizeContent->value, 'description' => 'According toNameDescriptionas optimize Content', 'parameters' => [ 'type' => 'object', 'properties' => [ 'prompt' => [ 'type' => 'string', 'description' => 'SystemNoticeContent', ], 'tools' => [ 'type' => 'array', 'items' => [ 'type' => 'string', ], 'description' => 'Recommendedtool Codelist Return tool codeField', ], ], 'required' => ['prompt'], ], ], ], // 3. optimize Nametool [ 'type' => 'function', 'function' => [ 'name' => BeDelightfulAgentOptimizationType::OptimizeName->value, 'description' => 'According toAllinfo optimize Name', 'parameters' => [ 'type' => 'object', 'properties' => [ 'name' => [ 'type' => 'string', 'description' => 'optimize NameMust be2-10CharacterNameCannotyes complete ', ], ], 'required' => ['name'], ], ], ], // 4. optimize Descriptiontool [ 'type' => 'function', 'function' => [ 'name' => BeDelightfulAgentOptimizationType::OptimizeDescription->value, 'description' => 'According toAllinfo optimize Description', 'parameters' => [ 'type' => 'object', 'properties' => [ 'description' => [ 'type' => 'string', 'description' => 'optimize Description', ], ], 'required' => ['description'], ], ], ], ]; 
}
 
    private function builduser Prompt(BeDelightfulAgentOptimizationType $optimizationType, BeDelightfulAgentEntity $agentEntity, array $availabletool s): string 
{
 $agentData = [ 'name' => $agentEntity->getName(), 'description' => $agentEntity->getDescription(), 'prompt' => $agentEntity->getPromptString(), 'tools' => $agentEntity->gettool s(), ]; // Noticeincluding in CharacterNoticein Otherwiseautomatic $combined = (string) ($agentData['name'] . $agentData['description'] . $agentData['prompt']); $languageHint = preg_match('/\p
{
Han
}
/u', $combined) ? 'zh' : 'auto'; $requestData = [ 'ot' => $optimizationType->value, 'data' => $agentData, 'rules' => [ 'tool' => 'single_call_match_type', 'name' => '2-10_chars_no_punct_no_sentence', 'desc' => '20-100_chars_value_focus', 'content' => 'preserve_depth_format_supplement_sections', 'ignore' => 'basic_tools_ignored', 'diverse' => 'must_diff_prev', 'no_copy' => 'forbidden_output_same_as_input', 'lang' => 'match_input_and_headers', ], 'meta' => [ 'ts' => time(), 'lang_hint' => $languageHint, 'src' => 'super_magic_agent_optimizer', ], ]; // Ifyes optimize Contentand HaveAvailabletool AddRequestDatain if ($optimizationType === BeDelightfulAgentOptimizationType::OptimizeContent && ! empty($availabletool s)) 
{
 $requestData['available_tools'] = array_values($availabletool s); 
}
 $jsonString = json_encode($requestData, JSON_UNESCAPED_UNICODE); $instruction = 'Optimize once according to rules, only call single tool corresponding to otInput(JSON)'; return $instruction . $jsonString; 
}
 
    private function extracttool CallResult(ChatCompletionResponse $response, BeDelightfulAgentEntity $agentEntity, array $availabletool s): BeDelightfulAgentEntity 
{
 // Parse response in tool call // IfDon't havetool call or Parse failedReturn original $assistantMessage = $response->getFirstChoice()?->getMessage(); if (! $assistantMessage instanceof AssistantMessage) 
{
 return $agentEntity; 
}
 if (! $assistantMessage->hastool Calls()) 
{
 $this->logger->info('No assistant message selected, returning original entity.'); return $agentEntity; 
}
 foreach ($assistantMessage->gettool Calls() as $toolCall) 
{
 $this->logger->info('tool_call', $toolCall->toArray()); $toolName = $toolCall->getName(); $arguments = $toolCall->getArguments(); switch ($toolName) 
{
 case BeDelightfulAgentOptimizationType::OptimizeNameDescription->value: if (isset($arguments['name'])) 
{
 $agentEntity->setName($arguments['name']); 
}
 if (isset($arguments['description'])) 
{
 $agentEntity->setDescription($arguments['description']); 
}
 break; case BeDelightfulAgentOptimizationType::OptimizeContent->value: if (isset($arguments['prompt'])) 
{
 // process Character \\n\\t\\r Convert toActualRowtable $processedPrompt = stripcslashes($arguments['prompt']); $promptData = [ 'version' => '1.0.0', 'structure' => [ 'string' => $processedPrompt, ], ]; $agentEntity->setPrompt($promptData); 
}
 // process tool RecommendedAddNewtool Modifyor delete Havetool if (isset($arguments['tools']) && is_array($arguments['tools'])) 
{
 foreach ($arguments['tools'] as $toolCode) 
{
 $tool = $this->createtool FromAvailabletool s($toolCode, $availabletool s); if ($tool) 
{
 $agentEntity->addtool ($tool); 
}
 
}
 
}
 break; case BeDelightfulAgentOptimizationType::OptimizeName->value: if (isset($arguments['name'])) 
{
 $agentEntity->setName($arguments['name']); 
}
 break; case BeDelightfulAgentOptimizationType::OptimizeDescription->value: if (isset($arguments['description'])) 
{
 $agentEntity->setDescription($arguments['description']); 
}
 break; 
}
 
}
 return $agentEntity; 
}
 /** * check optimize Condition. */ 
    private function checkOptimizationPreconditions(BeDelightfulAgentOptimizationType $optimizationType, BeDelightfulAgentEntity $agentEntity): bool 
{
 // IfAllContentEmptyRowoptimize if (empty($agentEntity->getName()) && empty($agentEntity->getDescription()) && empty($agentEntity->getPromptString())) 
{
 return true; 
}
 return false; 
}
 /** * FromAvailabletool list in Create BeDelightfulAgenttool Object */ 
    private function createtool FromAvailabletool s(string $toolCode, array $availabletool s): ?BeDelightfulAgenttool 
{
 // FirstFindThrough code FieldMatch if (isset($availabletool s[$toolCode])) 
{
 $toolinfo = $availabletool s[$toolCode]; return new BeDelightfulAgenttool ([ 'code' => $toolinfo ['code'], 'name' => $toolinfo ['name'] ?? '', 'description' => $toolinfo ['description'] ?? '', 'type' => $toolinfo ['type'] === 'builtin' ? 1 : 3, ]); 
}
 // SecondFindThrough name FieldMatch foreach ($availabletool s as $tool) 
{
 if (($tool['name'] ?? '') === $toolCode) 
{
 return new BeDelightfulAgenttool ([ 'code' => $tool['code'], 'name' => $tool['name'] ?? '', 'description' => $tool['description'] ?? '', 'type' => $tool['type'] === 'builtin' ? 1 : 3, ]); 
}
 
}
 // IfFindDon't haveReturn null return null; 
}
 
}
 
