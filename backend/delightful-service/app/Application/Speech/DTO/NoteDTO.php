<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

use function Hyperf\Translation\trans;

/**
 * 笔记DTO
 * useatASR总结middle笔记information.
 */
readonly class NoteDTO
{
    public function __construct(
        public string $content,
        public string $fileExtension
    ) {
    }

    /**
     * verifyfiletypewhethervalid.
     */
    public function isValidFileType(): bool
    {
        // supportfiletype
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
     * generatefile名.
     *
     * @param null|string $generatedTitle generatetitle,ifprovidethenuse {title}-笔记.{ext} format
     */
    public function generateFileName(?string $generatedTitle = null): string
    {
        if (! empty($generatedTitle)) {
            // usegeneratetitleformat:{title}-笔记.{ext}
            return sprintf('%s-%s.%s', $generatedTitle, trans('asr.file_names.note_suffix'), $this->getFileExtension());
        }

        // backtodefaultformat
        return sprintf('%s.%s', trans('asr.file_names.note_prefix'), $this->getFileExtension());
    }

    /**
     * checkwhetherhavecontent.
     */
    public function hasContent(): bool
    {
        return ! empty(trim($this->content));
    }

    /**
     * fromarraycreateinstance.
     *
     * @param array $data containcontentandfile_typearray
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['content'] ?? '',
            $data['file_type'] ?? 'md'
        );
    }

    /**
     * convertforarray.
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'file_type' => $this->fileExtension,
        ];
    }
}
