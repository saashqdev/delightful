<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Interfaces\Agent\Facade\Sandbox;

use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\Agent\Service\BeDelightfulAgentAppService;
use Delightful\BeDelightful\Interfaces\Agent\Assembler\BeDelightfulAgentAssembler;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class BeDelightfulAgentSandboxApi extends AbstractBeDelightfulSandboxApi
{
    #[Inject]
    protected BeDelightfulAgentAppService $superMagicAgentAppService;

    public function show(string $code)
    {
        $authorization = $this->getAuthorization();
        $withToolSchema = (bool) $this->request->input('with_tool_schema', false);
        $entity = $this->superMagicAgentAppService->show($authorization, $code, $withToolSchema);
        $withPromptString = (bool) $this->request->input('with_prompt_string', false);
        return BeDelightfulAgentAssembler::createDTO($entity, [], $withPromptString);
    }

    public function executeTool()
    {
        $authorization = $this->getAuthorization();
        $params = $this->request->all();
        return $this->superMagicAgentAppService->executeTool($authorization, $params);
    }
}
