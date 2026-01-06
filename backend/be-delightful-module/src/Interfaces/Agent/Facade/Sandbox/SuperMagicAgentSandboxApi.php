<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperDelightful\Interfaces\Agent\Facade\Sandbox;

use Delightful\ApiResponse\Annotation\ApiResponse;
use Delightful\SuperDelightful\Application\Agent\Service\SuperDelightfulAgentAppService;
use Delightful\SuperDelightful\Interfaces\Agent\Assembler\SuperDelightfulAgentAssembler;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class SuperDelightfulAgentSandboxApi extends AbstractSuperDelightfulSandboxApi
{
    #[Inject]
    protected SuperDelightfulAgentAppService $superDelightfulAgentAppService;

    public function show(string $code)
    {
        $authorization = $this->getAuthorization();
        $withToolSchema = (bool) $this->request->input('with_tool_schema', false);
        $entity = $this->superDelightfulAgentAppService->show($authorization, $code, $withToolSchema);
        $withPromptString = (bool) $this->request->input('with_prompt_string', false);
        return SuperDelightfulAgentAssembler::createDTO($entity, [], $withPromptString);
    }

    public function executeTool()
    {
        $authorization = $this->getAuthorization();
        $params = $this->request->all();
        return $this->superDelightfulAgentAppService->executeTool($authorization, $params);
    }
}
