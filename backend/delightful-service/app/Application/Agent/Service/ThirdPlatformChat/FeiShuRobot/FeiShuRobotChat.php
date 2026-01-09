<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service\ThirdPlatformChat\FeiShuRobot;

use App\Application\Agent\Service\ThirdPlatformChat\FeiShuRobot\FeiShu\Application;
use App\Application\Agent\Service\ThirdPlatformChat\ThirdPlatformChatEvent;
use App\Application\Agent\Service\ThirdPlatformChat\ThirdPlatformChatInterface;
use App\Application\Agent\Service\ThirdPlatformChat\ThirdPlatformChatMessage;
use App\Application\Agent\Service\ThirdPlatformChat\ThirdPlatformCreateGroup;
use App\Application\Agent\Service\ThirdPlatformChat\ThirdPlatformCreateSceneGroup;
use App\Application\Flow\ExecuteManager\Attachment\Attachment;
use App\Application\Flow\ExecuteManager\Attachment\LocalAttachment;
use App\Application\Flow\ExecuteManager\ExecutionData\TriggerDataUserExtInfo;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Infrastructure\Util\Locker\LockerInterface;
use Exception;
use Hyperf\Logger\LoggerFactory;
use InvalidArgumentException;
use JsonException;
use Nyholm\Psr7\Response;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class FeiShuRobotChat implements ThirdPlatformChatInterface
{
    /**
     * 飞书messagetypeconstant.
     */
    private const string MESSAGE_TYPE_TEXT = 'text';

    private const string MESSAGE_TYPE_IMAGE = 'image';

    private const string MESSAGE_TYPE_FILE = 'file';

    private const string MESSAGE_TYPE_POST = 'post';

    /**
     * 飞书聊天typeconstant.
     */
    private const string CHAT_TYPE_P2P = 'p2p';

    private const string CHAT_TYPE_GROUP = 'group';

    /**
     * 飞书事件typeconstant.
     */
    private const string EVENT_TYPE_MESSAGE_RECEIVE = 'im.message.receive_v1';

    /**
     * 锁定前缀
     */
    private const string LOCK_PREFIX = 'feishu_message_';

    /**
     * 锁定时间 (秒).
     */
    private const int LOCK_TTL = 7200;

    /**
     * 图片默认尺寸.
     */
    private const int DEFAULT_IMAGE_WIDTH = 300;

    private const int DEFAULT_IMAGE_HEIGHT = 300;

    /**
     * 飞书application实例.
     */
    private Application $application;

    /**
     * 日志记录器.
     */
    private LoggerInterface $logger;

    /**
     * 锁定器.
     */
    private LockerInterface $locker;

    private CacheInterface $cache;

    /**
     * 构造函数.
     *
     * @param array $options 飞书配置选项
     * @throws Exception 如果配置无效
     */
    public function __construct(array $options)
    {
        if (empty($options)) {
            throw new InvalidArgumentException('飞书机器人配置不能为null');
        }
        $options['http'] = [
            'base_uri' => 'https://open.feishu.cn',
        ];
        $this->logger = di(LoggerFactory::class)->get('FeiShuRobotChat');
        $this->locker = di(LockerInterface::class);
        $this->cache = di(CacheInterface::class);
        $this->application = new Application($options);
    }

    /**
     * parse聊天parameter.
     *
     * @param array $params receive到的parameter
     * @return ThirdPlatformChatMessage parse后的messageobject
     */
    public function parseChatParam(array $params): ThirdPlatformChatMessage
    {
        $chatMessage = new ThirdPlatformChatMessage();

        // handle服务器validate请求
        if (isset($params['challenge'])) {
            return $this->handleChallengeCheck($params, $chatMessage);
        }

        // 检查messageparameter是否有效
        if (empty($params['event']) || empty($params['header'])) {
            $this->logger->warning('飞书messageparameter无效', ['params' => $params]);
            $chatMessage->setEvent(ThirdPlatformChatEvent::None);
            return $chatMessage;
        }

        $messageId = $params['event']['message']['message_id'] ?? '';

        // 幂等性handle：使用messageID进行去重
        if (! $this->checkMessageIdLock($messageId)) {
            $this->logger->info('飞书message已handle过，跳过', ['message_id' => $messageId]);
            $chatMessage->setEvent(ThirdPlatformChatEvent::None);
            return $chatMessage;
        }

        $eventType = $params['header']['event_type'] ?? '';
        if ($eventType === self::EVENT_TYPE_MESSAGE_RECEIVE) {
            return $this->handleMessageReceive($params, $chatMessage);
        }

        $this->logger->info('未知的飞书事件type', ['event_type' => $eventType]);
        return $chatMessage;
    }

    /**
     * sendmessage.
     *
     * @param ThirdPlatformChatMessage $thirdPlatformChatMessage 平台message
     * @param MessageInterface $message 要send的message
     */
    public function sendMessage(ThirdPlatformChatMessage $thirdPlatformChatMessage, MessageInterface $message): void
    {
        // validatemessagetype
        if (! $message instanceof TextMessage) {
            $this->logger->warning('不支持的messagetype', ['message_type' => get_class($message)]);
            return;
        }

        // validateconversationtype
        if (! $thirdPlatformChatMessage->isOne() && ! $thirdPlatformChatMessage->isGroup()) {
            $this->logger->warning('不支持的conversationtype', ['conversation_type' => $thirdPlatformChatMessage->getType()]);
            return;
        }

        try {
            $content = $message->getContent();
            // parse Markdown 内容，convert为飞书富文本格式
            $postContent = $this->parseMarkdownToFeiShuPost($content);
            $data = [
                'receive_id' => $thirdPlatformChatMessage->getOriginConversationId(),
                'msg_type' => 'post',
                'content' => [
                    'zh_cn' => $postContent,
                ],
            ];

            $this->logger->info('SendMessageToFeiShu', [
                'receive_id' => $thirdPlatformChatMessage->getOriginConversationId(),
                'content_length' => strlen($content),
            ]);

            $this->application->message->send($data, 'chat_id');
        } catch (Exception $e) {
            $this->logger->error('send飞书messagefail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'receive_id' => $thirdPlatformChatMessage->getOriginConversationId(),
            ]);
        }
    }

    public function getThirdPlatformUserIdByMobiles(string $mobile): string
    {
        return '';
    }

    public function createSceneGroup(ThirdPlatformCreateSceneGroup $params): string
    {
        return '';
    }

    public function createGroup(ThirdPlatformCreateGroup $params): string
    {
        return '';
    }

    /**
     * handle服务器validate请求
     *
     * @param array $params 请求parameter
     * @param ThirdPlatformChatMessage $chatMessage 聊天messageobject
     * @return ThirdPlatformChatMessage handle后的messageobject
     */
    private function handleChallengeCheck(array $params, ThirdPlatformChatMessage $chatMessage): ThirdPlatformChatMessage
    {
        $this->logger->info('handle飞书服务器validate请求');

        $chatMessage->setEvent(ThirdPlatformChatEvent::CheckServer);
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['challenge' => $params['challenge']], JSON_UNESCAPED_UNICODE)
        );
        $chatMessage->setResponse($response);

        return $chatMessage;
    }

    /**
     * 检查messageID锁
     *
     * @param string $messageId messageID
     * @return bool 是否success锁定
     */
    private function checkMessageIdLock(string $messageId): bool
    {
        if (empty($messageId)) {
            $this->logger->warning('messageID为null，无法锁定');
            return false;
        }

        $lockKey = self::LOCK_PREFIX . $messageId;
        return $this->locker->mutexLock($lockKey, 'feishu', self::LOCK_TTL);
    }

    /**
     * handlereceive到的message.
     *
     * @param array $params 请求parameter
     * @param ThirdPlatformChatMessage $chatMessage 聊天messageobject
     * @return ThirdPlatformChatMessage handle后的messageobject
     */
    private function handleMessageReceive(array $params, ThirdPlatformChatMessage $chatMessage): ThirdPlatformChatMessage
    {
        $chatMessage->setEvent(ThirdPlatformChatEvent::ChatMessage);
        $messageId = $params['event']['message']['message_id'] ?? '';
        $organizationCode = $params['delightful_system']['organization_code'] ?? '';

        // 设置基本信息
        $this->setMessageBasicInfo($params, $chatMessage);

        // handlemessage内容
        $content = $this->decodeMessageContent($params['event']['message']['content'] ?? '');
        $messageType = $params['event']['message']['message_type'] ?? '';

        $result = $this->processMessageContent($messageType, $content, $chatMessage, $organizationCode, $messageId);

        if ($result === false) {
            // 不支持的messagetype，已send提示并设置事件为None
            return $chatMessage;
        }

        // 设置conversationID
        $this->setConversationId($params, $chatMessage);

        // 设置额外parameter
        $chatMessage->setParams([
            'message_id' => $messageId,
        ]);

        // 获取并设置user扩展信息
        try {
            $userExtInfo = $this->getUserExtInfo($params, $organizationCode);
            if ($userExtInfo !== null) {
                $chatMessage->setUserExtInfo($userExtInfo);
                $chatMessage->setNickname($userExtInfo->getNickname());
            }
        } catch (Exception $e) {
            $this->logger->warning('获取user扩展信息fail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $chatMessage;
    }

    /**
     * 获取user扩展信息.
     *
     * @param array $params 请求parameter
     * @param string $organizationCode organization代码
     * @return null|TriggerDataUserExtInfo user扩展信息object
     */
    private function getUserExtInfo(array $params, string $organizationCode): ?TriggerDataUserExtInfo
    {
        try {
            $openId = $params['event']['sender']['sender_id']['open_id'] ?? '';
            if (empty($openId)) {
                $this->logger->warning('userOpenID为null，无法获取user信息');
                return null;
            }

            // 缓存起来
            $cacheKey = "feishu_user_ext_info_{$openId}";
            if ($cacheValue = $this->cache->get($cacheKey)) {
                $userExtInfo = unserialize($cacheValue);
                if ($userExtInfo instanceof TriggerDataUserExtInfo) {
                    return $userExtInfo;
                }
            }

            // 从飞书API获取user信息
            $userInfo = $this->fetchUserInfoFromFeiShu($openId);
            if (empty($userInfo)) {
                return null;
            }

            // 创建user扩展信息object
            $realName = $userInfo['name'] ?? $openId;
            $nickname = ! empty($userInfo['nickname']) ? $userInfo['nickname'] : $realName;
            $userExtInfo = new TriggerDataUserExtInfo($organizationCode, $openId, $nickname, $realName);

            // 设置工号和position
            if (isset($userInfo['employee_no'])) {
                $userExtInfo->setWorkNumber($userInfo['employee_no']);
            }

            if (isset($userInfo['job_title'])) {
                $userExtInfo->setPosition($userInfo['job_title']);
            }

            $this->cache->set($cacheKey, serialize($userExtInfo), 7200);

            return $userExtInfo;
        } catch (Exception $e) {
            $this->logger->error('获取user信息exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * 从飞书API获取user信息.
     *
     * @param string $openId userOpenID
     * @return array user信息
     */
    private function fetchUserInfoFromFeiShu(string $openId): array
    {
        try {
            // 获取user基本信息
            $userInfo = $this->application->contact->user($openId);

            if (empty($userInfo) || ! isset($userInfo['user'])) {
                $this->logger->warning('从飞书获取user信息fail', ['open_id' => $openId]);
                return [];
            }

            return $userInfo['user'];
        } catch (Exception $e) {
            $this->logger->error('call飞书API获取user信息fail', [
                'open_id' => $openId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * parsemessage内容JSON.
     *
     * @param string $content 原始message内容
     * @return array parse后的内容array
     */
    private function decodeMessageContent(string $content): array
    {
        if (empty($content)) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (JsonException $e) {
            $this->logger->warning('parsemessage内容fail', [
                'content' => $content,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * 设置message基本信息.
     *
     * @param array $params 请求parameter
     * @param ThirdPlatformChatMessage $chatMessage 聊天messageobject
     */
    private function setMessageBasicInfo(array $params, ThirdPlatformChatMessage $chatMessage): void
    {
        $chatId = $params['event']['message']['chat_id'] ?? '';
        $openId = $params['event']['sender']['sender_id']['open_id'] ?? '';

        $chatMessage->setRobotCode($params['header']['app_id'] ?? '');
        $chatMessage->setUserId($openId);
        $chatMessage->setOriginConversationId($chatId);
        $chatMessage->setNickname($openId); // 初始设置为OpenID，后续会通过user信息更新
    }

    /**
     * 设置conversationID.
     *
     * @param array $params 请求parameter
     * @param ThirdPlatformChatMessage $chatMessage 聊天messageobject
     */
    private function setConversationId(array $params, ThirdPlatformChatMessage $chatMessage): void
    {
        $chatType = $params['event']['message']['chat_type'] ?? '';
        $robotCode = $chatMessage->getRobotCode();
        $originConversationId = $chatMessage->getOriginConversationId();

        if ($chatType === self::CHAT_TYPE_P2P) {
            $chatMessage->setType(1);
            $chatMessage->setConversationId(
                "{$robotCode}-{$originConversationId}_feishu_private_chat"
            );
        } elseif ($chatType === self::CHAT_TYPE_GROUP) {
            $chatMessage->setType(2);
            $chatMessage->setConversationId(
                "{$robotCode}-{$originConversationId}_feishu_group_chat"
            );
        } else {
            $this->logger->warning('未知的聊天type', ['chat_type' => $chatType]);
        }
    }

    /**
     * handlemessage内容.
     *
     * @param string $messageType messagetype
     * @param array $content message内容
     * @param ThirdPlatformChatMessage $chatMessage 聊天messageobject
     * @param string $organizationCode organization代码
     * @param string $messageId messageID
     * @return bool handle是否success
     */
    private function processMessageContent(
        string $messageType,
        array $content,
        ThirdPlatformChatMessage $chatMessage,
        string $organizationCode,
        string $messageId
    ): bool {
        $attachments = [];
        $message = '';

        try {
            switch ($messageType) {
                case self::MESSAGE_TYPE_TEXT:
                    $message = $content['text'] ?? '';
                    break;
                case self::MESSAGE_TYPE_IMAGE:
                    $filePath = $this->getFileFromFeiShu($messageId, $content['image_key'] ?? '', 'image');
                    if (! empty($filePath)) {
                        $attachments[] = $this->createAttachment($filePath, $organizationCode);
                    }
                    break;
                case self::MESSAGE_TYPE_FILE:
                    $filePath = $this->getFileFromFeiShu($messageId, $content['file_key'] ?? '', 'file');
                    if (! empty($filePath)) {
                        $attachments[] = $this->createAttachment($filePath, $organizationCode);
                    }
                    break;
                case self::MESSAGE_TYPE_POST:
                    // handle富文本message
                    $data = $this->parsePostContentToText($content, $organizationCode, $messageId);
                    $message = $data['markdown'];
                    $attachments = $data['attachments'];
                    break;
                default:
                    // send不支持的messagetype提示
                    $this->sendUnsupportedMessageTypeNotice($chatMessage->getOriginConversationId());
                    $chatMessage->setEvent(ThirdPlatformChatEvent::None);
                    return false;
            }

            $chatMessage->setMessage($message);
            $chatMessage->setAttachments($attachments);

            return true;
        } catch (Exception $e) {
            $this->logger->error('handlemessage内容fail', [
                'message_type' => $messageType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // senderror提示
            $this->sendErrorNotice($chatMessage->getOriginConversationId());
            $chatMessage->setEvent(ThirdPlatformChatEvent::None);
            return false;
        }
    }

    /**
     * 从飞书获取文件.
     *
     * @param string $messageId messageID
     * @param string $fileKey 文件Key
     * @param string $type 文件type
     * @return string 文件路径
     */
    private function getFileFromFeiShu(string $messageId, string $fileKey, string $type): string
    {
        if (empty($fileKey)) {
            $this->logger->warning('文件Key为null', [
                'message_id' => $messageId,
                'file_type' => $type,
            ]);
            return '';
        }

        try {
            return $this->application->file->getIMFile($messageId, $fileKey, $type);
        } catch (Exception $e) {
            $this->logger->error('获取飞书文件fail', [
                'message_id' => $messageId,
                'file_key' => $fileKey,
                'file_type' => $type,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * 创建附件object
     *
     * @param string $filePath 文件路径
     * @param string $organizationCode organization代码
     * @return Attachment 附件object
     */
    private function createAttachment(string $filePath, string $organizationCode): Attachment
    {
        try {
            return (new LocalAttachment($filePath))->getAttachment($organizationCode);
        } catch (Exception $e) {
            $this->logger->error('创建附件objectfail', [
                'file_path' => $filePath,
                'organization_code' => $organizationCode,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * send不支持的messagetype通知.
     *
     * @param string $receiverId receive者ID
     */
    private function sendUnsupportedMessageTypeNotice(string $receiverId): void
    {
        $data = [
            'receive_id' => $receiverId,
            'msg_type' => 'text',
            'content' => [
                'text' => '暂不支持的messagetype',
            ],
        ];
        $this->application->message->send($data, 'chat_id');

        $this->logger->info('已send不支持messagetype通知', ['receive_id' => $receiverId]);
    }

    /**
     * senderror通知.
     *
     * @param string $receiverId receive者ID
     */
    private function sendErrorNotice(string $receiverId): void
    {
        $data = [
            'receive_id' => $receiverId,
            'msg_type' => 'text',
            'content' => [
                'text' => 'handlemessage时发生error，请稍后再试',
            ],
        ];
        $this->application->message->send($data, 'chat_id');

        $this->logger->info('已senderror通知', ['receive_id' => $receiverId]);
    }

    /**
     * parse飞书富文本内容为Markdown文本.
     *
     * @param array $content 飞书富文本内容
     * @param string $organizationCode organization代码
     * @param string $messageId messageID
     * @return array parse结果，containmarkdown文本和附件
     */
    private function parsePostContentToText(array $content, string $organizationCode, string $messageId): array
    {
        $markdown = '';
        $attachments = [];

        // 提取标题（如果有）
        if (! empty($content['title'])) {
            $markdown .= "# {$content['title']}\n\n";
        }

        // 优先使用中文内容，如果没有则使用英文内容
        $postContent = $content['content'] ?? [];

        foreach ($postContent as $paragraph) {
            foreach ($paragraph as $element) {
                $tag = $element['tag'] ?? '';
                $markdown .= $this->processContentElement($tag, $element, $attachments, $organizationCode, $messageId);
            }
            $markdown .= "\n";
        }

        return [
            'markdown' => $markdown,
            'attachments' => $attachments,
        ];
    }

    /**
     * handle内容元素.
     *
     * @param string $tag 元素标签
     * @param array $element 元素内容
     * @param array &$attachments 附件列表
     * @param string $organizationCode organization代码
     * @param string $messageId messageID
     * @return string handle后的Markdown文本
     */
    private function processContentElement(
        string $tag,
        array $element,
        array &$attachments,
        string $organizationCode,
        string $messageId
    ): string {
        try {
            switch ($tag) {
                case 'text':
                    return $element['text'] ?? '';
                case 'a':
                    return "[{$element['text']}]({$element['href']})";
                case 'at':
                    return "@{$element['user_name']}";
                case 'img':
                case 'media':
                    return $this->processImageElement($element, $attachments, $organizationCode, $messageId);
                case 'emotion':
                    $emotionKey = $element['emoji_type'] ?? '';
                    return "[:{$emotionKey}:]";
                case 'br':
                    return "\n";
                case 'hr':
                    return "\n---\n";
                case 'code':
                    $language = $element['language'] ?? '';
                    $text = $element['text'] ?? '';
                    return "\n```{$language}\n{$text}\n```\n";
                case 'md':
                    $text = $element['text'] ?? '';
                    return "{$text}\n";
                default:
                    // 对于未知的标签，尝试提取文本内容
                    return $element['text'] ?? '';
            }
        } catch (Exception $e) {
            $this->logger->warning('handle内容元素fail', [
                'tag' => $tag,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * handle图片元素.
     *
     * @param array $element 元素内容
     * @param array &$attachments 附件列表
     * @param string $organizationCode organization代码
     * @param string $messageId messageID
     * @return string handle后的Markdown文本
     */
    private function processImageElement(
        array $element,
        array &$attachments,
        string $organizationCode,
        string $messageId
    ): string {
        $fileKey = $element['file_key'] ?? $element['image_key'] ?? '';
        $filePath = $this->getFileFromFeiShu($messageId, $fileKey, 'image');

        if (! empty($filePath)) {
            try {
                $attachments[] = $this->createAttachment($filePath, $organizationCode);
            } catch (Exception $e) {
                $this->logger->warning('添加图片附件fail', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return '';
    }

    /**
     * parseMarkdown内容，convert为飞书富文本格式
     * 只handle图片，其他内容全部使用md样式.
     *
     * @param string $markdown Markdown内容
     * @return array 飞书富文本格式
     */
    private function parseMarkdownToFeiShuPost(string $markdown): array
    {
        // initialize飞书富文本结构
        $postContent = [
            'title' => '',
            'content' => [],
        ];

        // 使用正则表达式匹配Markdown中的图片
        $pattern = '/!\[(.*?)\]\((.*?)\)/';

        // 如果没有图片，直接returnmd格式
        if (! preg_match_all($pattern, $markdown, $matches, PREG_OFFSET_CAPTURE)) {
            $postContent['content'][] = [
                [
                    'tag' => 'md',
                    'text' => $markdown,
                ],
            ];
            return $postContent;
        }

        // handlecontain图片的情况
        $lastPosition = 0;
        $contentBlocks = [];

        // 遍历所有匹配到的图片
        foreach ($matches[0] as $index => $match) {
            $fullMatch = $match[0];
            $position = $match[1];
            $url = $matches[2][$index][0];

            // 添加图片前的文本（如果有）
            $this->addTextBlockIfNotEmpty(
                $contentBlocks,
                substr($markdown, $lastPosition, $position - $lastPosition)
            );

            // handle图片
            $this->processImageBlock($contentBlocks, $url, $fullMatch);

            // 更新handle位置
            $lastPosition = $position + strlen($fullMatch);
        }

        // 添加最后一个图片后的文本（如果有）
        $this->addTextBlockIfNotEmpty(
            $contentBlocks,
            substr($markdown, $lastPosition)
        );

        $postContent['content'] = $contentBlocks;
        return $postContent;
    }

    /**
     * 添加文本块（如果不为null）.
     *
     * @param array &$contentBlocks 内容块array
     * @param string $text 要添加的文本
     */
    private function addTextBlockIfNotEmpty(array &$contentBlocks, string $text): void
    {
        $text = trim($text);
        if ($text !== '') {
            $contentBlocks[] = [
                [
                    'tag' => 'md',
                    'text' => $text,
                ],
            ];
        }
    }

    /**
     * handle图片块.
     *
     * @param array &$contentBlocks 内容块array
     * @param string $url 图片URL
     * @param string $fallbackText 上传fail时的回退文本
     */
    private function processImageBlock(array &$contentBlocks, string $url, string $fallbackText): void
    {
        try {
            $imageKey = $this->application->image->uploadByUrl($url);
            $contentBlocks[] = [
                [
                    'tag' => 'img',
                    'image_key' => $imageKey,
                    'width' => self::DEFAULT_IMAGE_WIDTH,
                    'height' => self::DEFAULT_IMAGE_HEIGHT,
                ],
            ];
        } catch (Exception $e) {
            $this->logger->notice('UploadImageToFeiShuFailed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            // 如果上传fail，添加图片URL作为md文本
            $contentBlocks[] = [
                [
                    'tag' => 'md',
                    'text' => $fallbackText,
                ],
            ];
        }
    }
}
