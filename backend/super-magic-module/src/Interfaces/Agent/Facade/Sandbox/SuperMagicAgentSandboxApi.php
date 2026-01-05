<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperMagic\Interfaces\Agent\Facade\Sandbox;

use Delightful\ApiResponse\Annotation\ApiResponse;
use Delightful\SuperMagic\Application\Agent\Service\SuperMagicAgentAppService;
use Delightful\SuperMagic\Interfaces\Agent\Assembler\SuperMagicAgentAssembler;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class SuperMagicAgentSandboxApi extends AbstractSuperMagicSandboxApi
{
    #[Inject]
    protected SuperMagicAgentAppService $superMagicAgentAppService;

    public function show(string $code)
    {
        $authorization = $this->getAuthorization();
        $withToolSchema = (bool) $this->request->input('with_tool_schema', false);
        $entity = $this->superMagicAgentAppService->show($authorization, $code, $withToolSchema);
        $withPromptString = (bool) $this->request->input('with_prompt_string', false);
        return SuperMagicAgentAssembler::createDTO($entity, [], $withPromptString);
    }

    public function executeTool()
    {
        $authorization = $this->getAuthorization();
        $params = $this->request->all();
        return $this->superMagicAgentAppService->executeTool($authorization, $params);
    }
}
