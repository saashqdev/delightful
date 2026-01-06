<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Agent\Facade\Open;

use App\Application\Agent\Service\DelightfulBotThirdPlatformChatAppService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;

readonly class ThirdPlatformChatApi
{
    public function __construct(
        protected RequestInterface $request,
        protected DelightfulBotThirdPlatformChatAppService $delightfulBotThirdPlatformChatAppService
    ) {
    }

    public function chat(): ?ResponseInterface
    {
        $key = $this->request->query('key', '');
        $message = $this->delightfulBotThirdPlatformChatAppService->chat($key, $this->request->all());
        return $message->getResponse();
    }
}
