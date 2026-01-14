<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Agent\Facade;

use App\Application\Agent\Service\DelightfulBotThirdPlatformChatAppService;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulBotThirdPlatformChatQuery;
use App\Interfaces\Agent\Assembler\DelightfulBotThirdPlatformChatAssembler;
use App\Interfaces\Agent\DTO\DelightfulBotThirdPlatformChatDTO;
use BeDelightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse('low_code')]
class DelightfulBotThirdPlatformChatApi extends AbstractApi
{
    #[Inject]
    protected DelightfulBotThirdPlatformChatAppService $delightfulBotThirdPlatformChatAppService;

    #[Inject]
    protected DelightfulBotThirdPlatformChatAssembler $delightfulBotThirdPlatformChatAssembler;

    public function save()
    {
        $authorization = $this->getAuthorization();
        $DTO = new DelightfulBotThirdPlatformChatDTO($this->request->all());
        $DO = $this->delightfulBotThirdPlatformChatAssembler->createDO($DTO);
        $entity = $this->delightfulBotThirdPlatformChatAppService->save($authorization, $DO);
        return $this->delightfulBotThirdPlatformChatAssembler->createDTO($entity);
    }

    public function listByBotId(string $botId)
    {
        $authorization = $this->getAuthorization();
        $page = $this->createPage();
        $data = $this->delightfulBotThirdPlatformChatAppService->listByBotId($authorization, $botId, $page);
        return $this->delightfulBotThirdPlatformChatAssembler->createPageDTO($data['total'], $data['list'], $page, true);
    }

    public function queries(string $botId)
    {
        $authorization = $this->getAuthorization();
        $page = $this->createPage();
        $query = new DelightfulBotThirdPlatformChatQuery();
        $query->setBotId($botId);
        $data = $this->delightfulBotThirdPlatformChatAppService->queries($authorization, $query, $page);
        return $this->delightfulBotThirdPlatformChatAssembler->createPageDTO($data['total'], $data['list'], $page);
    }

    public function destroy(string $id)
    {
        $authorization = $this->getAuthorization();
        $this->delightfulBotThirdPlatformChatAppService->destroy($authorization, $id);
    }
}
