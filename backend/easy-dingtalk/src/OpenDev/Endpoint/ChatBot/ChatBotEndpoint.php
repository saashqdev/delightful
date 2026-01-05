<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Endpoint\ChatBot;

use Delightful\EasyDingTalk\Kernel\Exceptions\BadRequestException;
use Delightful\EasyDingTalk\OpenDev\Api\ChatBot\DownloadFileApi;
use Delightful\EasyDingTalk\OpenDev\Api\ChatBot\SendGroupMessageApi;
use Delightful\EasyDingTalk\OpenDev\Api\ChatBot\SendOneOnOneChatMessagesApi;
use Delightful\EasyDingTalk\OpenDev\Endpoint\OpenDevEndpoint;
use Delightful\EasyDingTalk\OpenDev\Parameter\ChatBot\DownloadFileParameter;
use Delightful\EasyDingTalk\OpenDev\Parameter\ChatBot\SendGroupMessageParameter;
use Delightful\EasyDingTalk\OpenDev\Parameter\ChatBot\SendOneOnOneChatMessagesParameter;
use Delightful\EasyDingTalk\OpenDev\Result\ChatBot\DownloadFileResult;
use Delightful\EasyDingTalk\OpenDev\Result\ChatBot\SendGroupMessageResult;
use Delightful\EasyDingTalk\OpenDev\Result\ChatBot\SendOneOnOneChatMessagesResult;
use GuzzleHttp\RequestOptions;

class ChatBotEndpoint extends OpenDevEndpoint
{
    public function sendOneOnOneChatMessages(SendOneOnOneChatMessagesParameter $parameter): SendOneOnOneChatMessagesResult
    {
        $parameter->validate();

        $api = new SendOneOnOneChatMessagesApi();
        $api->setOptions([
            RequestOptions::HEADERS => [
                'x-acs-dingtalk-access-token' => $parameter->getAccessToken(),
            ],
            RequestOptions::JSON => [
                'robotCode' => $parameter->getRobotCode(),
                'userIds' => $parameter->getUserIds(),
                'msgKey' => $parameter->getMsgKey(),
                'msgParam' => $parameter->getMsgParam(),
            ],
        ]);

        $response = $this->send($api);
        $data = json_decode($response->getBody()->getContents(), true);
        $processQueryKey = $data['processQueryKey'] ?? null;
        if (empty($processQueryKey) || $response->getStatusCode() !== 200) {
            throw new BadRequestException('Send failed');
        }
        return new SendOneOnOneChatMessagesResult($data);
    }

    public function sendGroupMessage(SendGroupMessageParameter $parameter): SendGroupMessageResult
    {
        $parameter->validate();

        $api = new SendGroupMessageApi();
        $api->setOptions([
            RequestOptions::HEADERS => [
                'x-acs-dingtalk-access-token' => $parameter->getAccessToken(),
            ],
            RequestOptions::JSON => [
                'robotCode' => $parameter->getRobotCode(),
                'openConversationId' => $parameter->getOpenConversationId(),
                'msgKey' => $parameter->getMsgKey(),
                'msgParam' => $parameter->getMsgParam(),
            ],
        ]);

        $response = $this->send($api);
        $data = json_decode($response->getBody()->getContents(), true);
        $processQueryKey = $data['processQueryKey'] ?? null;
        if (empty($processQueryKey) || $response->getStatusCode() !== 200) {
            throw new BadRequestException('Send failed');
        }
        return new SendGroupMessageResult($data);
    }

    public function downloadFile(DownloadFileParameter $parameter): DownloadFileResult
    {
        $parameter->validate();

        $api = new DownloadFileApi();
        $api->setOptions([
            RequestOptions::HEADERS => [
                'x-acs-dingtalk-access-token' => $parameter->getAccessToken(),
            ],
            RequestOptions::JSON => [
                'robotCode' => $parameter->getRobotCode(),
                'downloadCode' => $parameter->getDownloadCode(),
            ],
        ]);

        $response = $this->send($api);
        $data = json_decode($response->getBody()->getContents(), true);
        $downloadUrl = $data['downloadUrl'] ?? null;
        if (empty($downloadUrl) || $response->getStatusCode() !== 200) {
            throw new BadRequestException('Download failed');
        }
        return new DownloadFileResult($data);
    }
}
