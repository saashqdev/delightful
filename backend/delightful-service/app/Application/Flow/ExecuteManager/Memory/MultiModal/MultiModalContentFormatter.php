<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Memory\MultiModal;

use App\Application\Flow\ExecuteManager\Attachment\AttachmentInterface;

/**
 * multi-modalstatecontentformatizationtool
 * useat统oneprocessdifferentscenariodownmulti-modalstatecontentformatization.
 */
class MultiModalContentFormatter
{
    /**
     * will haveattachmentformatizationtotextmiddle.
     *
     * @param string $originalContent originaltextcontent
     * @param string $visionResponse visualanalyzeresult
     * @param AttachmentInterface[] $attachments  haveattachmentarray
     * @return string formatizationbacktextcontent
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
     * formatizationimagecontenttotext
     * supportsingle张imageandmultipleimagescenario.
     *
     * @param string $originalContent originaltextcontent
     * @param string $visionResponse visualanalyzeresult
     * @param AttachmentInterface[] $imageAttachments imageattachmentarray
     * @return string addimageinfotextcontent
     */
    protected static function formatImageContent(
        string $originalContent,
        string $visionResponse,
        array $imageAttachments
    ): string {
        // ifnothaveimageattachment,directlyreturnoriginalcontent
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
     * formatizationnonimageattachmenttotext.
     *
     * @param string $originalContent originaltextcontent
     * @param AttachmentInterface[] $nonImageAttachments nonimageattachmentarray
     * @return string addnonimageattachmentinfotextcontent
     */
    protected static function formatNonImageAttachments(
        string $originalContent,
        array $nonImageAttachments
    ): string {
        // ifnothaveattachment,directlyreturnoriginalcontent
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
