<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Memory\MultiModal;

use App\Application\Flow\ExecuteManager\Attachment\AttachmentInterface;

/**
 * 多模态contentformat化工具
 * 用于统一processdifferent场景下的多模态contentformat化.
 */
class MultiModalContentFormatter
{
    /**
     * 将所有attachmentformat化到文本中.
     *
     * @param string $originalContent original文本content
     * @param string $visionResponse 视觉分析result
     * @param AttachmentInterface[] $attachments 所有attachmentarray
     * @return string format化后的文本content
     */
    public static function formatAllAttachments(
        string $originalContent,
        string $visionResponse,
        array $attachments,
    ): string {
        if (empty($attachments)) {
            return $originalContent;
        }

        // 分离image和非imageattachment
        $imageAttachments = [];
        $nonImageAttachments = [];

        foreach ($attachments as $attachment) {
            if ($attachment->isImage()) {
                $imageAttachments[] = $attachment;
            } else {
                $nonImageAttachments[] = $attachment;
            }
        }

        // process非imageattachment
        $content = self::formatNonImageAttachments($originalContent, $nonImageAttachments);

        // processimageattachment
        return self::formatImageContent($content, $visionResponse, $imageAttachments);
    }

    /**
     * format化imagecontent到文本
     * 支持单张image和多张image场景.
     *
     * @param string $originalContent original文本content
     * @param string $visionResponse 视觉分析result
     * @param AttachmentInterface[] $imageAttachments imageattachmentarray
     * @return string 添加了imageinfo的文本content
     */
    protected static function formatImageContent(
        string $originalContent,
        string $visionResponse,
        array $imageAttachments
    ): string {
        // 如果没有imageattachment，直接returnoriginalcontent
        if (empty($imageAttachments)) {
            return $originalContent;
        }

        $content = $originalContent;

        if (! empty($content)) {
            $content .= "\n\n";
        }
        $content .= "<image组 description=\"{$visionResponse}\">\n";
        foreach ($imageAttachments as $attachment) {
            $url = $attachment->getUrl();
            $name = $attachment->getName();
            if (! empty($url)) {
                $content .= "  ![{$name}]({$url})\n";
            }
        }
        $content .= '</image组>';
        return $content;
    }

    /**
     * format化非imageattachment到文本.
     *
     * @param string $originalContent original文本content
     * @param AttachmentInterface[] $nonImageAttachments 非imageattachmentarray
     * @return string 添加了非imageattachmentinfo的文本content
     */
    protected static function formatNonImageAttachments(
        string $originalContent,
        array $nonImageAttachments
    ): string {
        // 如果没有attachment，直接returnoriginalcontent
        if (empty($nonImageAttachments)) {
            return $originalContent;
        }

        $content = $originalContent;

        // 添加非imageattachment的link
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
