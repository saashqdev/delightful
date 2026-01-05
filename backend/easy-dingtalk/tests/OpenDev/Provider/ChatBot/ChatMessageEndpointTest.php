<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\Test\OpenDev\Provider\ChatBot;

use Dtyq\EasyDingTalk\OpenDev\Parameter\ChatBot\DownloadFileParameter;
use Dtyq\EasyDingTalk\OpenDev\Parameter\ChatBot\SendGroupMessageParameter;
use Dtyq\EasyDingTalk\OpenDev\Parameter\ChatBot\SendOneOnOneChatMessagesParameter;
use Dtyq\EasyDingTalk\OpenDev\Result\ChatBot\DownloadFileResult;
use Dtyq\EasyDingTalk\OpenDev\Result\ChatBot\SendGroupMessageResult;
use Dtyq\EasyDingTalk\OpenDev\Result\ChatBot\SendOneOnOneChatMessagesResult;
use Dtyq\EasyDingTalk\Test\OpenDev\OpenDevEndpointBaseTestCase;

/**
 * @internal
 * @coversNothing
 */
class ChatMessageEndpointTest extends OpenDevEndpointBaseTestCase
{
    public function testSendOneOnOneChatMessages()
    {
        $openDev = $this->createOpenDevFactory('magic-flow');
        $param = new SendOneOnOneChatMessagesParameter($openDev->accessTokenEndpoint->get());
        $param->setRobotCode('dinge6lvoxj27cm6rg0t');
        $param->setUserIds(['246716352326311484']);
        $param->setMsgKey('sampleMarkdown');
        $param->setMsgParam(json_encode([
            'title' => 'hello',
            'text' => 'Search with Magic Ai
![avatar](https://help-static-aliyun-doc.aliyuncs.com/assets/img/zh-CN/1922002861/p634074.png)

There are one default supported search engine: Google.

### Google Search
Visit this address to get the Google Search API key [SearchApi Google Search API Key](https://www.searchapi.io/)
Then visit this address to get the Google Search cx parameter [SearchApi Google Search cx](https://programmablesearchengine.google.com/about/)

## Setup LLM and KV
> [!NOTE]
> Note! Google Search API requires proxy configuration, otherwise Google will block access. Please set the proxy in the .env file.
> Redis is currently used to cache search results, so you need to configure redis env locally.

## Build and Run
Start frontend service:
```shell
cd static/web && npm install && npm run dev
```
Start backend service:
```shell
php bin/hyperf.php start
```
    ',
        ], JSON_UNESCAPED_UNICODE));
        $result = $openDev->chatBotEndpoint->sendOneOnOneChatMessages($param);
        $this->assertInstanceOf(SendOneOnOneChatMessagesResult::class, $result);
    }

    public function testSendGroupMessage()
    {
        $openDev = $this->createOpenDevFactory('magic-flow');
        $param = new SendGroupMessageParameter($openDev->accessTokenEndpoint->get());
        $param->setRobotCode('dinge6lvoxj27cm6rg0t');
        $param->setOpenConversationId('cideXwrh5j0nC1U3bf4rDERGQ==');
        $param->setMsgKey('sampleMarkdown');
        $param->setMsgParam(json_encode([
            'title' => 'hello',
            'text' => '# Hello world',
        ], JSON_UNESCAPED_UNICODE));
        $result = $openDev->chatBotEndpoint->sendGroupMessage($param);
        var_dump($result);
        $this->assertInstanceOf(SendGroupMessageResult::class, $result);
    }

    public function testDownloadFile()
    {
        $openDev = $this->createOpenDevFactory('magic-flow');
        $param = new DownloadFileParameter($openDev->accessTokenEndpoint->get());
        $param->setRobotCode('dinge6lvoxj27cm6rg0t');
        $param->setDownloadCode('mIofN681YE3f/+m+NntqpTt7FQXj2AghbDS/D/xcZmlSKqlfqQ8Fp+dWOg6yh+5+FgiMCaG6l8z7fraG8P7uNDRA90yjO2jF5H+wDR/KQGqzsbiJ3Mg/D02SBddCacTS2L90004aa/jp3cXDJ79NnDzf1T7vqA8jHV3DW3m5IXVmRa02nT5UZ7kzfVaTgmsDsq/dm1DzS38V+Fisxow2aF7JkUrlZ2vw/6y5ybiNJSw=');
        $result = $openDev->chatBotEndpoint->downloadFile($param);
        var_dump($result);
        $this->assertInstanceOf(DownloadFileResult::class, $result);
    }
}
