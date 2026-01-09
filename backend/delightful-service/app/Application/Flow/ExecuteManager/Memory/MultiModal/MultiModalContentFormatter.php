<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Memory\MultiModal;

use App\Application\Flow\ExecuteManager\Attachment\AttachmentInterface;

/**
 * 多模statecontentformat化tool
 * useat统oneprocessdifferent场景down多模statecontentformat化.
 */
class MultiModalContentFormatter
{
    /**
     * will所haveattachmentformat化totextmiddle.
     *
     * @param string $originalContent originaltextcontent
     * @param string $visionResponse 视觉analyzeresult
     * @param AttachmentInterface[] $attachments 所haveattachmentarray
     * @return string format化backtextcontent
     */
    public static function formatAllAttachments(
        string $originalContent,
        string $visionResponse,
        array $attachments,
    ): string {
        if (empty($attachments)) {
            return $originalContent;
        }

        // minute离imageandnonimageattachment
        $imageAttachments = [];
        $nonImageAttachments = [];

        foreach ($attachments as $attachment) {
            if ($attachment->isImage()) {
                $imageAttachments[] = $attachment;
            } else {
                $nonImageAttachments[] = $attachment;
            }
        }

        // processnonimageattachment
        $content = self::formatNonImageAttachments($originalContent, $nonImageAttachments);

        // processimageattachment
        return self::formatImageContent($content, $visionResponse, $imageAttachments);
    }

    /**
     * format化imagecontenttotext
     * supportsingle张imageand多张image场景.
     *
     * @param string $originalContent originaltextcontent
     * @param string $visionResponse 视觉analyzeresult
     * @param AttachmentInterface[] $imageAttachments imageattachmentarray
     * @return string addimageinfotextcontent
     */
    protected static function formatImageContent(
        string $originalContent,
        string $visionResponse,
        array $imageAttachments
    ): string {
        // ifnothaveimageattachment，直接returnoriginalcontent
        if (empty($imageAttachments)) {
            return $originalContent;
        }

        $content = $originalContent;

        if (! empty($content)) {
            $content .= "\n\n";
        }
        $content .= "<imagegroup description=\"{$visionResponse}\">\n";
        foreach ($imageAttachments as $attachment) {
            $url = $attachment->getUrl();
            $name = $attachment->getName();
            if (! empty($url)) {
                $content .= "  ![{$name}]({$url})\n";
            }
        }
        $content .= '</imagegroup>';
        return $content;
    }

    /**
     * format化nonimageattachmenttotext.
     *
     * @param string $originalContent originaltextcontent
     * @param AttachmentInterface[] $nonImageAttachments nonimageattachmentarray
     * @return string addnonimageattachmentinfotextcontent
     */
    protected static function formatNonImageAttachments(
        string $originalContent,
        array $nonImageAttachments
    ): string {
        // ifnothaveattachment，直接returnoriginalcontent
        if (empty($nonImageAttachments)) {
            return $originalContent;
        }

        $content = $originalContent;

        // addnonimageattachmentlink
        foreach ($nonImageAttachments as $attachment) {
            $url = $attachment->getUrl();
            $name = $attachment->getName();
            if (! empty($url)) {
                if (! empty($content)) {
                    $content .= ' ';
                }
                $content .= "[{$name}]({$url})";
            }
        }

        return $content;
    }
}
