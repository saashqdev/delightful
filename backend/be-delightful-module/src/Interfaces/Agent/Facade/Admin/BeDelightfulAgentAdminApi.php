<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Agent\Facade\Admin;

use App\Application\Flow\Service\MagicFlowAppService;
use App\Domain\Flow\Entity\MagicFlowtool SetEntity;
use App\Infrastructure\Util\ShadowCode\ShadowCode;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\Agent\Service\BeDelightfulAgentAiOptimizeAppService;
use Delightful\BeDelightful\Application\Agent\Service\BeDelightfulAgentAppService;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\query \BeDelightfulAgentquery ;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentOptimizationType;
use Delightful\BeDelightful\Interfaces\Agent\Assembler\Builtintool Assembler;
use Delightful\BeDelightful\Interfaces\Agent\Assembler\BeDelightfulAgentAssembler;
use Delightful\BeDelightful\Interfaces\Agent\DTO\Builtintool DTO;
use Delightful\BeDelightful\Interfaces\Agent\DTO\BeDelightfulAgentDTO;
use Delightful\BeDelightful\Interfaces\Agent\FormRequest\BeDelightfulAgentAiOptimizeFormRequest;
use Delightful\BeDelightful\Interfaces\Agent\FormRequest\BeDelightfulAgentOrderFormRequest;
use Delightful\BeDelightful\Interfaces\Agent\FormRequest\BeDelightfulAgentquery FormRequest;
use Delightful\BeDelightful\Interfaces\Agent\FormRequest\BeDelightfulAgentSaveFormRequest;
use Hyperf\Di\Annotation\Inject;
#[ApiResponse(version: 'low_code')]

class BeDelightfulAgentAdminApi extends AbstractBeDelightfulAdminApi 
{
 #[Inject] 
    protected BeDelightfulAgentAppService $BeDelightfulAgentAppService; #[Inject] 
    protected BeDelightfulAgentAiOptimizeAppService $BeDelightfulAgentAiOptimizeAppService; #[Inject] 
    protected MagicFlowAppService $magicFlowAppService; 
    public function save(BeDelightfulAgentSaveFormRequest $request) 
{
 $authorization = $this->getAuthorization(); $requestData = $request->validated(); $DTO = new BeDelightfulAgentDTO($requestData); $promptShadow = $request->input('prompt_shadow'); if ($promptShadow) 
{
 $promptShadow = json_decode(ShadowCode::unShadow($promptShadow), true); $DTO->setPrompt($promptShadow); 
}
 $DO = BeDelightfulAgentAssembler::createDO($DTO); $entity = $this->BeDelightfulAgentAppService->save($authorization, $DO); $users = $this->BeDelightfulAgentAppService->getuser s($entity->getOrganizationCode(), [$entity->getcreator (), $entity->getModifier()]); return BeDelightfulAgentAssembler::createDTO($entity, $users); 
}
 
    public function queries(BeDelightfulAgentquery FormRequest $request) 
{
 $authorization = $this->getAuthorization(); $requestData = $request->validated(); $query = new BeDelightfulAgentquery ($requestData); $page = $this->createPage(); $result = $this->BeDelightfulAgentAppService->queries($authorization, $query, $page); return BeDelightfulAgentAssembler::createCategorizedlist DTO( frequent: $result['frequent'], all: $result['all'], total: $result['total'] ); 
}
 
    public function show(string $code) 
{
 $authorization = $this->getAuthorization(); $withtool Schema = (bool) $this->request->input('with_tool_schema', false); $entity = $this->BeDelightfulAgentAppService->show($authorization, $code, $withtool Schema); $withPromptString = (bool) $this->request->input('with_prompt_string', false); $users = $this->BeDelightfulAgentAppService->getuser s($entity->getOrganizationCode(), [$entity->getcreator (), $entity->getModifier()]); return BeDelightfulAgentAssembler::createDTO($entity, $users, $withPromptString); 
}
 
    public function destroy(string $code) 
{
 $authorization = $this->getAuthorization(); $result = $this->BeDelightfulAgentAppService->delete($authorization, $code); return ['success' => $result]; 
}
 
    public function enable(string $code) 
{
 $authorization = $this->getAuthorization(); $entity = $this->BeDelightfulAgentAppService->enable($authorization, $code); $users = $this->BeDelightfulAgentAppService->getuser s($entity->getOrganizationCode(), [$entity->getcreator (), $entity->getModifier()]); return BeDelightfulAgentAssembler::createDTO($entity, $users); 
}
 
    public function disable(string $code) 
{
 $authorization = $this->getAuthorization(); $entity = $this->BeDelightfulAgentAppService->disable($authorization, $code); $users = $this->BeDelightfulAgentAppService->getuser s($entity->getOrganizationCode(), [$entity->getcreator (), $entity->getModifier()]); return BeDelightfulAgentAssembler::createDTO($entity, $users); 
}
 /** * SaveColumnorder . */ 
    public function saveOrder(BeDelightfulAgentOrderFormRequest $request) 
{
 $authorization = $this->getAuthorization(); $requestData = $request->validated(); $orderConfig = [ 'frequent' => $requestData['frequent'] ?? [], 'all' => $requestData['all'], ]; $this->BeDelightfulAgentAppService->saveOrderConfig($authorization, $orderConfig); return ['message' => 'Agent order saved successfully']; 
}
 /** * GetBuilt-intool list . */ 
    public function tools() 
{
 return Builtintool Assembler::createtool Categorylist DTO(); 
}
 /** * AIoptimize . */ 
    public function aiOptimize(BeDelightfulAgentAiOptimizeFormRequest $request) 
{
 $authorization = $this->getAuthorization(); $requestData = $request->validated(); // Createoptimize TypeEnumInstanceFormRequest Validate EnsureValid $optimizationType = BeDelightfulAgentOptimizationType::fromString($requestData['optimization_type']); // Using BeDelightfulAgentAssembler Create $DTO = new BeDelightfulAgentDTO($requestData['agent']); $promptShadow = $request->input('agent.prompt_shadow'); if ($promptShadow) 
{
 $promptShadow = json_decode(ShadowCode::unShadow($promptShadow), true); $DTO->setPrompt($promptShadow); 
}
 $agentEntity = BeDelightfulAgentAssembler::createDO($DTO); // only Atoptimize Contentquery tool info $availabletool s = []; if ($optimizationType === BeDelightfulAgentOptimizationType::OptimizeContent) 
{
 // current user Availabletool list $builtintool s = Builtintool Assembler::createtool list DTO(); $customtool Sets = $this->magicFlowAppService->querytool Sets($authorization, false, false)['list'] ?? []; // MergeBuilt-intool Customtool as Format $availabletool s = $this->mergeAvailabletool s($builtintool s, $customtool Sets); 
}
 // call optimize Service $optimizedEntity = $this->BeDelightfulAgentAiOptimizeAppService->optimizeAgent( $authorization, $optimizationType, $agentEntity, $availabletool s ); return [ 'optimization_type' => $optimizationType->value, 'agent' => BeDelightfulAgentAssembler::createDTO($optimizedEntity), ]; 
}
 /** * MergeBuilt-intool Customtool as Format. * @param array<Builtintool DTO> $builtintool s * @param array<MagicFlowtool SetEntity> $customtool Sets */ 
    private function mergeAvailabletool s(array $builtintool s, array $customtool Sets): array 
{
 $tools = []; // process Built-intool foreach ($builtintool s as $tool) 
{
 $tools[$tool->getCode()] = [ 'code' => $tool->getCode(), 'name' => $tool->getName(), 'description' => $tool->getDescription(), 'required' => $tool->isRequired(), 'type' => 'builtin', ]; 
}
 // process Customtool foreach ($customtool Sets as $customtool Set) 
{
 foreach ($customtool Set->gettool s() as $tool) 
{
 $tools[$tool['code']] = [ 'code' => $tool['code'], 'name' => $tool['name'], 'description' => $tool['description'], 'required' => false, 'type' => 'custom', ]; 
}
 
}
 return $tools; 
}
 
}
 
