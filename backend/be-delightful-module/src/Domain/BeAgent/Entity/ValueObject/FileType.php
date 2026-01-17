<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * File type enum.
 */
enum FileType: string
{
    /**
     * User upload.
     */
    case USER_UPLOAD = 'user_upload';

    /**
     * Processing.
     */
    case PROCESS = 'process';

    /**
     * Browser.
     */
    case BROWSER = 'browser';

    /**
     * System auto upload.
     */
    case SYSTEM_AUTO_UPLOAD = 'system_auto_upload';

    /**
     * Tool message content.
     */
    case TOOL_MESSAGE_CONTENT = 'tool_message_content';

    /**
     * Document.
     */
    case DOCUMENT = 'document';

    /**
     * Auto sync.
     */
    case AUTO_SYNC = 'auto_sync';

    /**
     * Directory.
     */
    case DIRECTORY = 'directory';

    /**
     * Get file type name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::USER_UPLOAD => 'User upload',
            self::PROCESS => 'Processing',
            self::BROWSER => 'Browser',
            self::SYSTEM_AUTO_UPLOAD => 'System auto upload',
            self::TOOL_MESSAGE_CONTENT => 'Tool message content',
            self::DOCUMENT => 'Document',
            self::AUTO_SYNC => 'Auto sync',
            self::DIRECTORY => 'Directory',
        };
    }

    /**
     * Get file type description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::USER_UPLOAD => 'File manually uploaded by user',
            self::PROCESS => 'File generated during processing',
            self::BROWSER => 'File obtained through browser',
            self::SYSTEM_AUTO_UPLOAD => 'File automatically uploaded by system',
            self::TOOL_MESSAGE_CONTENT => 'File content contained in tool messages',
            self::DOCUMENT => 'Document type file',
            self::AUTO_SYNC => 'File automatically synced',
            self::DIRECTORY => 'Directory',
        };
    }
}
