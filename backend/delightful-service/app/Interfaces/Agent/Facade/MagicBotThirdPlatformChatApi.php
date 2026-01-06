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
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse('low_code')]
class DelightfulBotThirdPlatformChatApi extends AbstractApi
{
    #[Inject]
    protected DelightfulBotThirdPlatformChatAppService $magicBotThirdPlatformChatAppService;

    #[Inject]
    protected DelightfulBotThirdPlatformChatAssembler $magicBotThirdPlatformChatAssembler;

    public function save()
    {
        $authorization = $this->getAuthorization();
        $DTO = new DelightfulBotThirdPlatformChatDTO($this->request->all());
        $DO = $this->magicBotThirdPlatformChatAssembler->createDO($DTO);
        $entity = $this->magicBotThirdPlatformChatAppService->save($authorization, $DO);
        return $this->magicBotThirdPlatformChatAssembler->createDTO($entity);
    }

    public function listByBotId(string $botId)
    {
        $authorization = $this->getAuthorization();
        $page = $this->createPage();
        $data = $this->magicBotThirdPlatformChatAppService->listByBotId($authorization, $botId, $page);
        return $this->magicBotThirdPlatformChatAssembler->createPageDTO($data['total'], $data['list'], $page, true);
    }

    public function queries(string $botId)
    {
        $authorization = $this->getAuthorization();
        $page = $this->createPage();
        $query = new DelightfulBotThirdPlatformChatQuery();
        $query->setBotId($botId);
        $data = $this->magicBotThirdPlatformChatAppService->queries($authorization, $query, $page);
        return $this->magicBotThirdPlatformChatAssembler->createPageDTO($data['total'], $data['list'], $page);
    }

    public function destroy(string $id)
    {
        $authorization = $this->getAuthorization();
        $this->magicBotThirdPlatformChatAppService->destroy($authorization, $id);
    }
}
