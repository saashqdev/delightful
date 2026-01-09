<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Service;

use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Application\Speech\Assembler\AsrPromptAssembler;
use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Application\Speech\DTO\NoteDTO;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\ErrorCode\AsrErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Hyperf\Contract\TranslatorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * ASR 标题generate服务
 * 负责according todifferent场景generate录音总结标题.
 */
readonly class AsrTitleGeneratorService
{
    public function __construct(
        private DelightfulChatMessageAppService $delightfulChatMessageAppService,
        private TaskFileDomainService $taskFileDomainService,
        private DelightfulUserDomainService $delightfulUserDomainService,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * according todifferent场景generate标题.
     *
     * 场景一：有 asr_stream_content（前端实时录音），直接用内容generate标题
     * 场景二：有 file_id（upload已有file），buildhint词generate标题
     *
     * @param DelightfulUserAuthorization $userAuthorization userauthorization
     * @param string $asrStreamContent ASRstream识别内容
     * @param null|string $fileId fileID
     * @param null|NoteDTO $note 笔记内容
     * @param string $taskKey task键（用于log）
     * @return null|string generate的标题
     */
    public function generateTitleForScenario(
        DelightfulUserAuthorization $userAuthorization,
        string $asrStreamContent,
        ?string $fileId,
        ?NoteDTO $note,
        string $taskKey
    ): ?string {
        try {
            $language = $this->translator->getLocale() ?: 'zh_CN';

            // 场景一：有 asr_stream_content（前端实时录音）
            if (! empty($asrStreamContent)) {
                $customPrompt = AsrPromptAssembler::getTitlePrompt($asrStreamContent, $note, $language);
                $title = $this->delightfulChatMessageAppService->summarizeTextWithCustomPrompt(
                    $userAuthorization,
                    $customPrompt
                );
                return $this->sanitizeTitle($title);
            }

            // 场景二：有 file_id（upload已有file）
            if (! empty($fileId)) {
                $fileEntity = $this->taskFileDomainService->getById((int) $fileId);
                if ($fileEntity === null) {
                    $this->logger->warning('generate标题时未找到file', [
                        'file_id' => $fileId,
                        'task_key' => $taskKey,
                    ]);
                    return null;
                }

                // 获取audiofile名称
                $audioFileName = $fileEntity->getFileName();

                // build笔记file名（如果有）
                $noteFileName = null;
                if ($note !== null && $note->hasContent()) {
                    $noteFileName = $note->generateFileName();
                }

                // builduser请求message（模拟userchatmessage）
                $userRequestMessage = $this->buildUserRequestMessage($audioFileName, $noteFileName);

                // use AsrPromptAssembler buildhint词
                $customPrompt = AsrPromptAssembler::getTitlePromptForUploadedFile(
                    $userRequestMessage,
                    $language
                );
                $title = $this->delightfulChatMessageAppService->summarizeTextWithCustomPrompt(
                    $userAuthorization,
                    $customPrompt
                );
                return $this->sanitizeTitle($title);
            }

            return null;
        } catch (Throwable $e) {
            $this->logger->warning('generate标题fail', [
                'task_key' => $taskKey,
                'has_asr_content' => ! empty($asrStreamContent),
                'has_file_id' => ! empty($fileId),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 从taskstatusgenerate标题（usesave的 ASR 内容和笔记内容）.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @return string generate的标题（fail时returndefault标题）
     */
    public function generateFromTaskStatus(AsrTaskStatusDTO $taskStatus): string
    {
        try {
            // use上报时save的语种，如果没有则usecurrent语种
            $language = $taskStatus->language ?: $this->translator->getLocale() ?: 'zh_CN';

            $this->logger->info('use语种generate标题', [
                'task_key' => $taskStatus->taskKey,
                'language' => $language,
                'has_asr_content' => ! empty($taskStatus->asrStreamContent),
                'has_note' => ! empty($taskStatus->noteContent),
            ]);

            // 如果有 ASR stream内容，use它generate标题
            if (! empty($taskStatus->asrStreamContent)) {
                // build笔记 DTO（如果有）
                $note = null;
                if (! empty($taskStatus->noteContent)) {
                    $note = new NoteDTO(
                        $taskStatus->noteContent,
                        $taskStatus->noteFileType ?? 'md'
                    );
                }

                // 获取完整的录音总结hint词
                $customPrompt = AsrPromptAssembler::getTitlePrompt(
                    $taskStatus->asrStreamContent,
                    $note,
                    $language
                );

                // usecustomizehint词generate标题
                $userAuthorization = $this->getUserAuthorizationFromUserId($taskStatus->userId);
                $title = $this->delightfulChatMessageAppService->summarizeTextWithCustomPrompt(
                    $userAuthorization,
                    $customPrompt
                );

                return $this->sanitizeTitle($title);
            }

            // 如果没有 ASR 内容，returndefault标题
            return $this->generateDefaultDirectoryName();
        } catch (Throwable $e) {
            $this->logger->warning('generate标题fail，usedefault标题', [
                'task_key' => $taskStatus->taskKey,
                'error' => $e->getMessage(),
            ]);
            return $this->generateDefaultDirectoryName();
        }
    }

    /**
     * 清洗标题，移除file/目录不allow的字符并截断length.
     *
     * @param string $title original标题
     * @return string 清洗后的标题
     */
    public function sanitizeTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            return '';
        }

        // 移除illegal字符 \/:*?"<>|
        $title = preg_replace('/[\\\\\/:*?"<>|]/u', '', $title) ?? '';
        // 压缩null白
        $title = preg_replace('/\s+/u', ' ', $title) ?? '';
        // 限制length，避免过长路径
        if (mb_strlen($title) > 50) {
            $title = mb_substr($title, 0, 50);
        }

        return $title;
    }

    /**
     * generatedefault的目录名称.
     *
     * @param null|string $customTitle customize标题
     * @return string 目录名称
     */
    public function generateDefaultDirectoryName(?string $customTitle = null): string
    {
        $base = $customTitle ?: $this->translator->trans('asr.directory.recordings_summary_folder');
        return sprintf('%s_%s', $base, date('Ymd_His'));
    }

    /**
     * 为file直传场景generate标题（仅according tofile名）.
     *
     * @param DelightfulUserAuthorization $userAuthorization userauthorization
     * @param string $fileName file名
     * @param string $taskKey task键（用于log）
     * @return null|string generate的标题
     */
    public function generateTitleForFileUpload(
        DelightfulUserAuthorization $userAuthorization,
        string $fileName,
        string $taskKey
    ): ?string {
        try {
            $language = $this->translator->getLocale() ?: 'zh_CN';

            // builduser请求message（模拟userchatmessage）
            $userRequestMessage = $this->buildUserRequestMessage($fileName, null);

            // use AsrPromptAssembler buildhint词
            $customPrompt = AsrPromptAssembler::getTitlePromptForUploadedFile(
                $userRequestMessage,
                $language
            );

            $title = $this->delightfulChatMessageAppService->summarizeTextWithCustomPrompt(
                $userAuthorization,
                $customPrompt
            );

            return $this->sanitizeTitle($title);
        } catch (Throwable $e) {
            $this->logger->warning('为file直传generate标题fail', [
                'task_key' => $taskKey,
                'file_name' => $fileName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * builduser请求message（模拟userchatmessage，use国际化文本）.
     *
     * @param string $audioFileName audiofile名称
     * @param null|string $noteFileName 笔记file名称（可选）
     * @return string 格式化后的user请求
     */
    private function buildUserRequestMessage(string $audioFileName, ?string $noteFileName): string
    {
        if ($noteFileName !== null) {
            // 有笔记的情况："请帮我把 @年will方案discussion.webm 录音内容和 @年will笔记.md 的内容转化为一份超级产物"
            return sprintf(
                '%s@%s%s@%s%s',
                $this->translator->trans('asr.messages.summary_prefix_with_note'),
                $audioFileName,
                $this->translator->trans('asr.messages.summary_middle_with_note'),
                $noteFileName,
                $this->translator->trans('asr.messages.summary_suffix_with_note')
            );
        }

        // 只有audiofile的情况："请帮我把 @年will方案discussion.webm 录音内容转化为一份超级产物"
        return sprintf(
            '%s@%s%s',
            $this->translator->trans('asr.messages.summary_prefix'),
            $audioFileName,
            $this->translator->trans('asr.messages.summary_suffix')
        );
    }

    /**
     * 从userID获取userauthorizationobject.
     *
     * @param string $userId userID
     * @return DelightfulUserAuthorization userauthorizationobject
     */
    private function getUserAuthorizationFromUserId(string $userId): DelightfulUserAuthorization
    {
        $userEntity = $this->delightfulUserDomainService->getUserById($userId);
        if ($userEntity === null) {
            ExceptionBuilder::throw(AsrErrorCode::UserNotExist);
        }
        return DelightfulUserAuthorization::fromUserEntity($userEntity);
    }
}
