<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * FileTypeEnum. */

enum FileType: string 
{
 /** * user Upload. */ case USER_UPLOAD = 'user_upload'; /** * process . */ case PROCESS = 'process'; /** * . */ case BROWSER = 'browser'; /** * Systemautomatic Upload. */ case SYSTEM_AUTO_UPLOAD = 'system_auto_upload'; /** * tool MessageContent. */ case TOOL_MESSAGE_CONTENT = 'tool_message_content'; /** * Documentation. */ case DOCUMENT = 'document'; /** * automatic Sync. */ case AUTO_SYNC = 'auto_sync'; /** * Directory. */ case DIRECTORY = 'directory'; /** * GetFileTypeName. */ 
    public function getName(): string 
{
 return match ($this) 
{
 self::USER_UPLOAD => 'user Upload', self::PROCESS => 'process ', self::BROWSER => '', self::SYSTEM_AUTO_UPLOAD => 'Systemautomatic Upload', self::TOOL_MESSAGE_CONTENT => 'tool MessageContent', self::DOCUMENT => 'Documentation', self::AUTO_SYNC => 'automatic Sync', self::DIRECTORY => 'Directory', 
}
; 
}
 /** * GetFileTypeDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::USER_UPLOAD => 'user ManualUploadFile', self::PROCESS => 'Atprocess in File', self::BROWSER => 'ThroughGetFile', self::SYSTEM_AUTO_UPLOAD => 'Systemautomatic UploadFile', self::TOOL_MESSAGE_CONTENT => 'tool Messagein including FileContent', self::DOCUMENT => 'DocumentationTypeFile', self::AUTO_SYNC => 'automatic SyncFile', self::DIRECTORY => 'Directory', 
}
; 
}
 
}
 
