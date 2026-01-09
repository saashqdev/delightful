<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

use function Hyperf\Translation\trans;

/**
 * 笔记DTO
 * 用于ASR总结中的笔记information.
 */
readonly class NoteDTO
{
    public function __construct(
        public string $content,
        public string $fileExtension
    ) {
    }

    /**
     * 验证filetype是否有效.
     */
    public function isValidFileType(): bool
    {
        // 支持的filetype
        $supportedTypes = ['txt', 'md', 'json'];
        return in_array(strtolower($this->fileExtension), $supportedTypes, true);
    }

    /**
     * getfileextension名.
     */
    public function getFileExtension(): string
    {
        return strtolower($this->fileExtension);
    }

    /**
     * 生成file名.
     *
     * @param null|string $generatedTitle 生成的标题，if提供则use {title}-笔记.{ext} 格式
     */
    public function generateFileName(?string $generatedTitle = null): string
    {
        if (! empty($generatedTitle)) {
            // use生成的标题格式：{title}-笔记.{ext}
            return sprintf('%s-%s.%s', $generatedTitle, trans('asr.file_names.note_suffix'), $this->getFileExtension());
        }

        // 回退到默认格式
        return sprintf('%s.%s', trans('asr.file_names.note_prefix'), $this->getFileExtension());
    }

    /**
     * 检查是否有content.
     */
    public function hasContent(): bool
    {
        return ! empty(trim($this->content));
    }

    /**
     * 从arraycreate实例.
     *
     * @param array $data 包含content和file_type的array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['content'] ?? '',
            $data['file_type'] ?? 'md'
        );
    }

    /**
     * 转换为array.
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'file_type' => $this->fileExtension,
        ];
    }
}
