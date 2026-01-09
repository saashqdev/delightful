<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage;

use App\Domain\Chat\DTO\Message\TextContentInterface;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Infrastructure\Util\Tiptap\CustomExtension\ImageNode;
use App\Infrastructure\Util\Tiptap\TiptapUtil;
use Hyperf\Codec\Json;
use Tiptap\Editor;

class RichTextMessage extends AbstractAttachmentMessage implements TextContentInterface
{
    protected string $content = '';

    public function setContent(array|string $content): void
    {
        $this->content = is_array($content) ? Json::encode($content) : $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTextContent(): string
    {
        return TiptapUtil::getTextContent($this->getContent());
    }

    public function getFileIds(): array
    {
        $attachmentIds = $this->getAttachmentIds();
        // checkrich textmiddlewhetherhaveimage
        $content = $this->getContent();
        $editor = new Editor([
            'extensions' => [
                new ImageNode(),
            ],
        ]);
        $richTextContent = $editor->setContent($content)->getJSON();
        $richTextContent = Json::decode($richTextContent);
        if (is_array($richTextContent) && ! empty($richTextContent)) {
            $imageIds = $this->findImageFileIds($richTextContent);
            $attachmentIds = array_values(array_unique(array_merge($attachmentIds, $imageIds)));
        }
        return $attachmentIds;
    }

    /**
     * 递归find所haveimagesectionpoint file_id.
     *
     * @param array $data 要searchdataarray
     * @param int $maxDepth most大递归深degree
     * @return array<string> return找to所have file_id array
     */
    public function findImageFileIds(array $data, int $maxDepth = 512, int $currentDepth = 0): array
    {
        $fileIds = [];

        if ($currentDepth > $maxDepth) {
            return $fileIds;
        }

        foreach ($data as $key => $value) {
            if ($key === 'type' && $value === 'image' && isset($data['attrs']['file_id'])) {
                $fileIds[] = $data['attrs']['file_id'];
            } elseif ($key === 'content' && is_array($value)) {
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $fileIds = array_merge($fileIds, $this->findImageFileIds($item, $maxDepth, $currentDepth + 1));
                    }
                }
            }
        }

        return $fileIds;
    }

    protected function setMessageType(): void
    {
        $this->chatMessageType = ChatMessageType::RichText;
    }
}
