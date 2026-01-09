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
 * ASR titlegenerateservice
 * responsibleaccording todifferentscenariogeneraterecordingsummarytitle.
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
     * according todifferentscenariogeneratetitle.
     *
     * scenarioone:have asr_stream_content(front端实o clockrecording),directlyusecontentgeneratetitle
     * scenariotwo:have file_id(uploadalreadyhavefile),buildhint词generatetitle
     *
     * @param DelightfulUserAuthorization $userAuthorization userauthorization
     * @param string $asrStreamContent ASRstreamidentifycontent
     * @param null|string $fileId fileID
     * @param null|NoteDTO $note notecontent
     * @param string $taskKey taskkey(useatlog)
     * @return null|string generatetitle
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

            // scenarioone:have asr_stream_content(front端实o clockrecording)
            if (! empty($asrStreamContent)) {
                $customPrompt = AsrPromptAssembler::getTitlePrompt($asrStreamContent, $note, $language);
                $title = $this->delightfulChatMessageAppService->summarizeTextWithCustomPrompt(
                    $userAuthorization,
                    $customPrompt
                );
                return $this->sanitizeTitle($title);
            }

            // scenariotwo:have file_id(uploadalreadyhavefile)
            if (! empty($fileId)) {
                $fileEntity = $this->taskFileDomainService->getById((int) $fileId);
                if ($fileEntity === null) {
                    $this->logger->warning('generatetitleo clocknot找tofile', [
                        'file_id' => $fileId,
                        'task_key' => $taskKey,
                    ]);
                    return null;
                }

                // getaudiofilename
                $audioFileName = $fileEntity->getFileName();

                // buildnotefile名(ifhave)
                $noteFileName = null;
                if ($note !== null && $note->hasContent()) {
                    $noteFileName = $note->generateFileName();
                }

                // builduserrequestmessage(mockuserchatmessage)
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
            $this->logger->warning('generatetitlefail', [
                'task_key' => $taskKey,
                'has_asr_content' => ! empty($asrStreamContent),
                'has_file_id' => ! empty($fileId),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * fromtaskstatusgeneratetitle(usesave ASR contentandnotecontent).
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @return string generatetitle(failo clockreturndefaulttitle)
     */
    public function generateFromTaskStatus(AsrTaskStatusDTO $taskStatus): string
    {
        try {
            // useup报o clocksave语type,ifnothavethenusecurrent语type
            $language = $taskStatus->language ?: $this->translator->getLocale() ?: 'zh_CN';

            $this->logger->info('use语typegeneratetitle', [
                'task_key' => $taskStatus->taskKey,
                'language' => $language,
                'has_asr_content' => ! empty($taskStatus->asrStreamContent),
                'has_note' => ! empty($taskStatus->noteContent),
            ]);

            // ifhave ASR streamcontent,useitgeneratetitle
            if (! empty($taskStatus->asrStreamContent)) {
                // buildnote DTO(ifhave)
                $note = null;
                if (! empty($taskStatus->noteContent)) {
                    $note = new NoteDTO(
                        $taskStatus->noteContent,
                        $taskStatus->noteFileType ?? 'md'
                    );
                }

                // getcompleterecordingsummaryhint词
                $customPrompt = AsrPromptAssembler::getTitlePrompt(
                    $taskStatus->asrStreamContent,
                    $note,
                    $language
                );

                // usecustomizehint词generatetitle
                $userAuthorization = $this->getUserAuthorizationFromUserId($taskStatus->userId);
                $title = $this->delightfulChatMessageAppService->summarizeTextWithCustomPrompt(
                    $userAuthorization,
                    $customPrompt
                );

                return $this->sanitizeTitle($title);
            }

            // ifnothave ASR content,returndefaulttitle
            return $this->generateDefaultDirectoryName();
        } catch (Throwable $e) {
            $this->logger->warning('generatetitlefail,usedefaulttitle', [
                'task_key' => $taskStatus->taskKey,
                'error' => $e->getMessage(),
            ]);
            return $this->generateDefaultDirectoryName();
        }
    }

    /**
     * cleantitle,移exceptfile/directorynotallowcharacterandtruncatelength.
     *
     * @param string $title originaltitle
     * @return string cleanbacktitle
     */
    public function sanitizeTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            return '';
        }

        // 移exceptillegalcharacter \/:*?"<>|
        $title = preg_replace('/[\\\\\/:*?"<>|]/u', '', $title) ?? '';
        // compressnull白
        $title = preg_replace('/\s+/u', ' ', $title) ?? '';
        // limitlength,avoidpasslongpath
        if (mb_strlen($title) > 50) {
            $title = mb_substr($title, 0, 50);
        }

        return $title;
    }

    /**
     * generatedefaultdirectoryname.
     *
     * @param null|string $customTitle customizetitle
     * @return string directoryname
     */
    public function generateDefaultDirectoryName(?string $customTitle = null): string
    {
        $base = $customTitle ?: $this->translator->trans('asr.directory.recordings_summary_folder');
        return sprintf('%s_%s', $base, date('Ymd_His'));
    }

    /**
     * forfiledirect uploadscenariogeneratetitle(onlyaccording tofile名).
     *
     * @param DelightfulUserAuthorization $userAuthorization userauthorization
     * @param string $fileName file名
     * @param string $taskKey taskkey(useatlog)
     * @return null|string generatetitle
     */
    public function generateTitleForFileUpload(
        DelightfulUserAuthorization $userAuthorization,
        string $fileName,
        string $taskKey
    ): ?string {
        try {
            $language = $this->translator->getLocale() ?: 'zh_CN';

            // builduserrequestmessage(mockuserchatmessage)
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
            $this->logger->warning('forfiledirect uploadgeneratetitlefail', [
                'task_key' => $taskKey,
                'file_name' => $fileName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * builduserrequestmessage(mockuserchatmessage,useinternationalizationtext).
     *
     * @param string $audioFileName audiofilename
     * @param null|string $noteFileName notefilename(optional)
     * @return string format化backuserrequest
     */
    private function buildUserRequestMessage(string $audioFileName, ?string $noteFileName): string
    {
        if ($noteFileName !== null) {
            // havenotesituation:"please helpI @yearwillsolutiondiscussion.webm recordingcontentand @yearwillnote.md contentconversionforoneshare超levelproduct"
            return sprintf(
                '%s@%s%s@%s%s',
                $this->translator->trans('asr.messages.summary_prefix_with_note'),
                $audioFileName,
                $this->translator->trans('asr.messages.summary_middle_with_note'),
                $noteFileName,
                $this->translator->trans('asr.messages.summary_suffix_with_note')
            );
        }

        // onlyaudiofilesituation:"please helpI @yearwillsolutiondiscussion.webm recordingcontentconversionforoneshare超levelproduct"
        return sprintf(
            '%s@%s%s',
            $this->translator->trans('asr.messages.summary_prefix'),
            $audioFileName,
            $this->translator->trans('asr.messages.summary_suffix')
        );
    }

    /**
     * fromuserIDgetuserauthorizationobject.
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
