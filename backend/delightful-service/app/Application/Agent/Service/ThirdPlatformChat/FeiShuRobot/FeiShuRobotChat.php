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
     * Feishumessagetypeconstant.
     */
    private const string MESSAGE_TYPE_TEXT = 'text';

    private const string MESSAGE_TYPE_IMAGE = 'image';

    private const string MESSAGE_TYPE_FILE = 'file';

    private const string MESSAGE_TYPE_POST = 'post';

    /**
     * Feishuchattypeconstant.
     */
    private const string CHAT_TYPE_P2P = 'p2p';

    private const string CHAT_TYPE_GROUP = 'group';

    /**
     * Feishueventtypeconstant.
     */
    private const string EVENT_TYPE_MESSAGE_RECEIVE = 'im.message.receive_v1';

    /**
     * lock定front缀
     */
    private const string LOCK_PREFIX = 'feishu_message_';

    /**
     * lockschedulebetween (second).
     */
    private const int LOCK_TTL = 7200;

    /**
     * imagedefaultsize.
     */
    private const int DEFAULT_IMAGE_WIDTH = 300;

    private const int DEFAULT_IMAGE_HEIGHT = 300;

    /**
     * Feishuapplicationinstance.
     */
    private Application $application;

    /**
     * logrecord器.
     */
    private LoggerInterface $logger;

    /**
     * lock定器.
     */
    private LockerInterface $locker;

    private CacheInterface $cache;

    /**
     * constructfunction.
     *
     * @param array $options Feishuconfigurationoption
     * @throws Exception ifconfigurationinvalid
     */
    public function __construct(array $options)
    {
        if (empty($options)) {
            throw new InvalidArgumentException('Feishumachinepersonconfigurationnotcanfornull');
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
     * parsechatparameter.
     *
     * @param array $params receivetoparameter
     * @return ThirdPlatformChatMessage parsebackmessageobject
     */
    public function parseChatParam(array $params): ThirdPlatformChatMessage
    {
        $chatMessage = new ThirdPlatformChatMessage();

        // handleservice器validaterequest
        if (isset($params['challenge'])) {
            return $this->handleChallengeCheck($params, $chatMessage);
        }

        // checkmessageparameterwhethervalid
        if (empty($params['event']) || empty($params['header'])) {
            $this->logger->warning('Feishumessageparameterinvalid', ['params' => $params]);
            $chatMessage->setEvent(ThirdPlatformChatEvent::None);
            return $chatMessage;
        }

        $messageId = $params['event']['message']['message_id'] ?? '';

        // poweretcpropertyhandle:usemessageIDconductgo重
        if (! $this->checkMessageIdLock($messageId)) {
            $this->logger->info('Feishumessagealreadyhandlepass,skip', ['message_id' => $messageId]);
            $chatMessage->setEvent(ThirdPlatformChatEvent::None);
            return $chatMessage;
        }

        $eventType = $params['header']['event_type'] ?? '';
        if ($eventType === self::EVENT_TYPE_MESSAGE_RECEIVE) {
            return $this->handleMessageReceive($params, $chatMessage);
        }

        $this->logger->info('unknownFeishueventtype', ['event_type' => $eventType]);
        return $chatMessage;
    }

    /**
     * sendmessage.
     *
     * @param ThirdPlatformChatMessage $thirdPlatformChatMessage platformmessage
     * @param MessageInterface $message wantsendmessage
     */
    public function sendMessage(ThirdPlatformChatMessage $thirdPlatformChatMessage, MessageInterface $message): void
    {
        // validatemessagetype
        if (! $message instanceof TextMessage) {
            $this->logger->warning('not supportedmessagetype', ['message_type' => get_class($message)]);
            return;
        }

        // validateconversationtype
        if (! $thirdPlatformChatMessage->isOne() && ! $thirdPlatformChatMessage->isGroup()) {
            $this->logger->warning('not supportedconversationtype', ['conversation_type' => $thirdPlatformChatMessage->getType()]);
            return;
        }

        try {
            $content = $message->getContent();
            // parse Markdown content,convertforFeishurich textformat
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
            $this->logger->error('sendFeishumessagefail', [
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
     * handleservice器validaterequest
     *
     * @param array $params requestparameter
     * @param ThirdPlatformChatMessage $chatMessage chatmessageobject
     * @return ThirdPlatformChatMessage handlebackmessageobject
     */
    private function handleChallengeCheck(array $params, ThirdPlatformChatMessage $chatMessage): ThirdPlatformChatMessage
    {
        $this->logger->info('handleFeishuservice器validaterequest');

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
     * checkmessageIDlock
     *
     * @param string $messageId messageID
     * @return bool whethersuccesslock定
     */
    private function checkMessageIdLock(string $messageId): bool
    {
        if (empty($messageId)) {
            $this->logger->warning('messageIDfornull,no法lock定');
            return false;
        }

        $lockKey = self::LOCK_PREFIX . $messageId;
        return $this->locker->mutexLock($lockKey, 'feishu', self::LOCK_TTL);
    }

    /**
     * handlereceivetomessage.
     *
     * @param array $params requestparameter
     * @param ThirdPlatformChatMessage $chatMessage chatmessageobject
     * @return ThirdPlatformChatMessage handlebackmessageobject
     */
    private function handleMessageReceive(array $params, ThirdPlatformChatMessage $chatMessage): ThirdPlatformChatMessage
    {
        $chatMessage->setEvent(ThirdPlatformChatEvent::ChatMessage);
        $messageId = $params['event']['message']['message_id'] ?? '';
        $organizationCode = $params['delightful_system']['organization_code'] ?? '';

        // settingbasicinfo
        $this->setMessageBasicInfo($params, $chatMessage);

        // handlemessagecontent
        $content = $this->decodeMessageContent($params['event']['message']['content'] ?? '');
        $messageType = $params['event']['message']['message_type'] ?? '';

        $result = $this->processMessageContent($messageType, $content, $chatMessage, $organizationCode, $messageId);

        if ($result === false) {
            // not supportedmessagetype,alreadysendhintandsettingeventforNone
            return $chatMessage;
        }

        // settingconversationID
        $this->setConversationId($params, $chatMessage);

        // setting额outsideparameter
        $chatMessage->setParams([
            'message_id' => $messageId,
        ]);

        // getandsettinguserextensioninfo
        try {
            $userExtInfo = $this->getUserExtInfo($params, $organizationCode);
            if ($userExtInfo !== null) {
                $chatMessage->setUserExtInfo($userExtInfo);
                $chatMessage->setNickname($userExtInfo->getNickname());
            }
        } catch (Exception $e) {
            $this->logger->warning('getuserextensioninfofail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $chatMessage;
    }

    /**
     * getuserextensioninfo.
     *
     * @param array $params requestparameter
     * @param string $organizationCode organizationcode
     * @return null|TriggerDataUserExtInfo userextensioninfoobject
     */
    private function getUserExtInfo(array $params, string $organizationCode): ?TriggerDataUserExtInfo
    {
        try {
            $openId = $params['event']['sender']['sender_id']['open_id'] ?? '';
            if (empty($openId)) {
                $this->logger->warning('userOpenIDfornull,no法getuserinfo');
                return null;
            }

            // cacheupcome
            $cacheKey = "feishu_user_ext_info_{$openId}";
            if ($cacheValue = $this->cache->get($cacheKey)) {
                $userExtInfo = unserialize($cacheValue);
                if ($userExtInfo instanceof TriggerDataUserExtInfo) {
                    return $userExtInfo;
                }
            }

            // fromFeishuAPIgetuserinfo
            $userInfo = $this->fetchUserInfoFromFeiShu($openId);
            if (empty($userInfo)) {
                return null;
            }

            // createuserextensioninfoobject
            $realName = $userInfo['name'] ?? $openId;
            $nickname = ! empty($userInfo['nickname']) ? $userInfo['nickname'] : $realName;
            $userExtInfo = new TriggerDataUserExtInfo($organizationCode, $openId, $nickname, $realName);

            // setting工numberandposition
            if (isset($userInfo['employee_no'])) {
                $userExtInfo->setWorkNumber($userInfo['employee_no']);
            }

            if (isset($userInfo['job_title'])) {
                $userExtInfo->setPosition($userInfo['job_title']);
            }

            $this->cache->set($cacheKey, serialize($userExtInfo), 7200);

            return $userExtInfo;
        } catch (Exception $e) {
            $this->logger->error('getuserinfoexception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * fromFeishuAPIgetuserinfo.
     *
     * @param string $openId userOpenID
     * @return array userinfo
     */
    private function fetchUserInfoFromFeiShu(string $openId): array
    {
        try {
            // getuserbasicinfo
            $userInfo = $this->application->contact->user($openId);

            if (empty($userInfo) || ! isset($userInfo['user'])) {
                $this->logger->warning('fromFeishugetuserinfofail', ['open_id' => $openId]);
                return [];
            }

            return $userInfo['user'];
        } catch (Exception $e) {
            $this->logger->error('callFeishuAPIgetuserinfofail', [
                'open_id' => $openId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * parsemessagecontentJSON.
     *
     * @param string $content originalmessagecontent
     * @return array parsebackcontentarray
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
            $this->logger->warning('parsemessagecontentfail', [
                'content' => $content,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * settingmessagebasicinfo.
     *
     * @param array $params requestparameter
     * @param ThirdPlatformChatMessage $chatMessage chatmessageobject
     */
    private function setMessageBasicInfo(array $params, ThirdPlatformChatMessage $chatMessage): void
    {
        $chatId = $params['event']['message']['chat_id'] ?? '';
        $openId = $params['event']['sender']['sender_id']['open_id'] ?? '';

        $chatMessage->setRobotCode($params['header']['app_id'] ?? '');
        $chatMessage->setUserId($openId);
        $chatMessage->setOriginConversationId($chatId);
        $chatMessage->setNickname($openId); // initialsettingforOpenID,back续willpassuserinfoupdate
    }

    /**
     * settingconversationID.
     *
     * @param array $params requestparameter
     * @param ThirdPlatformChatMessage $chatMessage chatmessageobject
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
            $this->logger->warning('unknownchattype', ['chat_type' => $chatType]);
        }
    }

    /**
     * handlemessagecontent.
     *
     * @param string $messageType messagetype
     * @param array $content messagecontent
     * @param ThirdPlatformChatMessage $chatMessage chatmessageobject
     * @param string $organizationCode organizationcode
     * @param string $messageId messageID
     * @return bool handlewhethersuccess
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
                    // handlerich textmessage
                    $data = $this->parsePostContentToText($content, $organizationCode, $messageId);
                    $message = $data['markdown'];
                    $attachments = $data['attachments'];
                    break;
                default:
                    // sendnot supportedmessagetypehint
                    $this->sendUnsupportedMessageTypeNotice($chatMessage->getOriginConversationId());
                    $chatMessage->setEvent(ThirdPlatformChatEvent::None);
                    return false;
            }

            $chatMessage->setMessage($message);
            $chatMessage->setAttachments($attachments);

            return true;
        } catch (Exception $e) {
            $this->logger->error('handlemessagecontentfail', [
                'message_type' => $messageType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // senderrorhint
            $this->sendErrorNotice($chatMessage->getOriginConversationId());
            $chatMessage->setEvent(ThirdPlatformChatEvent::None);
            return false;
        }
    }

    /**
     * fromFeishugetfile.
     *
     * @param string $messageId messageID
     * @param string $fileKey fileKey
     * @param string $type filetype
     * @return string filepath
     */
    private function getFileFromFeiShu(string $messageId, string $fileKey, string $type): string
    {
        if (empty($fileKey)) {
            $this->logger->warning('fileKeyfornull', [
                'message_id' => $messageId,
                'file_type' => $type,
            ]);
            return '';
        }

        try {
            return $this->application->file->getIMFile($messageId, $fileKey, $type);
        } catch (Exception $e) {
            $this->logger->error('getFeishufilefail', [
                'message_id' => $messageId,
                'file_key' => $fileKey,
                'file_type' => $type,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * createattachmentobject
     *
     * @param string $filePath filepath
     * @param string $organizationCode organizationcode
     * @return Attachment attachmentobject
     */
    private function createAttachment(string $filePath, string $organizationCode): Attachment
    {
        try {
            return (new LocalAttachment($filePath))->getAttachment($organizationCode);
        } catch (Exception $e) {
            $this->logger->error('createattachmentobjectfail', [
                'file_path' => $filePath,
                'organization_code' => $organizationCode,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * sendnot supportedmessagetypenotify.
     *
     * @param string $receiverId receive者ID
     */
    private function sendUnsupportedMessageTypeNotice(string $receiverId): void
    {
        $data = [
            'receive_id' => $receiverId,
            'msg_type' => 'text',
            'content' => [
                'text' => '暂not supportedmessagetype',
            ],
        ];
        $this->application->message->send($data, 'chat_id');

        $this->logger->info('alreadysendnot supportedmessagetypenotify', ['receive_id' => $receiverId]);
    }

    /**
     * senderrornotify.
     *
     * @param string $receiverId receive者ID
     */
    private function sendErrorNotice(string $receiverId): void
    {
        $data = [
            'receive_id' => $receiverId,
            'msg_type' => 'text',
            'content' => [
                'text' => 'handlemessageo clockhair生error,请稍backagain试',
            ],
        ];
        $this->application->message->send($data, 'chat_id');

        $this->logger->info('alreadysenderrornotify', ['receive_id' => $receiverId]);
    }

    /**
     * parseFeishurich textcontentforMarkdowntext.
     *
     * @param array $content Feishurich textcontent
     * @param string $organizationCode organizationcode
     * @param string $messageId messageID
     * @return array parseresult,containmarkdowntextandattachment
     */
    private function parsePostContentToText(array $content, string $organizationCode, string $messageId): array
    {
        $markdown = '';
        $attachments = [];

        // extracttitle(ifhave)
        if (! empty($content['title'])) {
            $markdown .= "# {$content['title']}\n\n";
        }

        // priorityusemiddle文content,ifnothavethenuseEnglishcontent
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
     * handlecontentyuan素.
     *
     * @param string $tag yuan素tag
     * @param array $element yuan素content
     * @param array &$attachments attachmentcolumntable
     * @param string $organizationCode organizationcode
     * @param string $messageId messageID
     * @return string handlebackMarkdowntext
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
                    // toatunknowntag,tryextracttextcontent
                    return $element['text'] ?? '';
            }
        } catch (Exception $e) {
            $this->logger->warning('handlecontentyuan素fail', [
                'tag' => $tag,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * handleimageyuan素.
     *
     * @param array $element yuan素content
     * @param array &$attachments attachmentcolumntable
     * @param string $organizationCode organizationcode
     * @param string $messageId messageID
     * @return string handlebackMarkdowntext
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
                $this->logger->warning('addimageattachmentfail', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return '';
    }

    /**
     * parseMarkdowncontent,convertforFeishurich textformat
     * onlyhandleimage,othercontentall部usemd样type.
     *
     * @param string $markdown Markdowncontent
     * @return array Feishurich textformat
     */
    private function parseMarkdownToFeiShuPost(string $markdown): array
    {
        // initializeFeishurich textstructure
        $postContent = [
            'title' => '',
            'content' => [],
        ];

        // usejustthentable达typematchMarkdownmiddleimage
        $pattern = '/!\[(.*?)\]\((.*?)\)/';

        // ifnothaveimage,directlyreturnmdformat
        if (! preg_match_all($pattern, $markdown, $matches, PREG_OFFSET_CAPTURE)) {
            $postContent['content'][] = [
                [
                    'tag' => 'md',
                    'text' => $markdown,
                ],
            ];
            return $postContent;
        }

        // handlecontainimagesituation
        $lastPosition = 0;
        $contentBlocks = [];

        // traverse所havematchtoimage
        foreach ($matches[0] as $index => $match) {
            $fullMatch = $match[0];
            $position = $match[1];
            $url = $matches[2][$index][0];

            // addimagefronttext(ifhave)
            $this->addTextBlockIfNotEmpty(
                $contentBlocks,
                substr($markdown, $lastPosition, $position - $lastPosition)
            );

            // handleimage
            $this->processImageBlock($contentBlocks, $url, $fullMatch);

            // updatehandleposition
            $lastPosition = $position + strlen($fullMatch);
        }

        // addmostnextimagebacktext(ifhave)
        $this->addTextBlockIfNotEmpty(
            $contentBlocks,
            substr($markdown, $lastPosition)
        );

        $postContent['content'] = $contentBlocks;
        return $postContent;
    }

    /**
     * addtextpiece(ifnotfornull).
     *
     * @param array &$contentBlocks contentpiecearray
     * @param string $text wantaddtext
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
     * handleimagepiece.
     *
     * @param array &$contentBlocks contentpiecearray
     * @param string $url imageURL
     * @param string $fallbackText uploadfailo clockbacktext
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
            // ifuploadfail,addimageURLasformdtext
            $contentBlocks[] = [
                [
                    'tag' => 'md',
                    'text' => $fallbackText,
                ],
            ];
        }
    }
}
